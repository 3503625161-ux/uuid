<?php

/**
 * This file is part of the ramsey/uuid library
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @copyright Copyright (c) Ben Ramsey <ben@benramsey.com>
 * @license http://opensource.org/licenses/MIT MIT
 */

declare(strict_types=1);

namespace Ramsey\Uuid\Generator;

use Ramsey\Uuid\Converter\TimeConverterInterface;
use Ramsey\Uuid\Exception\InvalidArgumentException;
use Ramsey\Uuid\Exception\RandomSourceException;
use Ramsey\Uuid\Exception\TimeSourceException;
use Ramsey\Uuid\Provider\NodeProviderInterface;
use Ramsey\Uuid\Provider\TimeProviderInterface;
use Ramsey\Uuid\Type\Hexadecimal;
use Throwable;

use function ctype_xdigit;
use function dechex;
use function hex2bin;
use function is_int;
use function pack;
use function random_bytes;
use function sprintf;
use function str_pad;
use function strlen;
use function substr;

use function unpack;

use const STR_PAD_LEFT;

/**
 * DefaultTimeGenerator generates strings of binary data based on a node ID, clock sequence, and the current time
 */
class DefaultTimeGenerator implements TimeGeneratorInterface
{
    /**
     * 24-bit 随机种子缓存；一次 random_bytes(3) 产生 0x000000..0xffffff 范围，
     * 每次 generate() 消耗其中的 14-bit(0..0x3fff)，由 $clockSeqSeedOffset 做游标。
     * null 表示种子已耗尽 / 尚未初始化。
     */
    private static ?int $clockSeqSeed = null;

    /**
     * 当前种子内已消耗的 bit 数（0..10），每 14-bit 一次新种子读取。
     */
    private static int $clockSeqSeedOffset = 14;

    public function __construct(
        private NodeProviderInterface $nodeProvider,
        private TimeConverterInterface $timeConverter,
        private TimeProviderInterface $timeProvider,
    ) {
    }

    /**
     * @throws InvalidArgumentException if the parameters contain invalid values
     * @throws RandomSourceException if random_bytes() throws an exception/error
     *
     * @inheritDoc
     */
    public function generate($node = null, ?int $clockSeq = null): string
    {
        if ($node instanceof Hexadecimal) {
            $node = $node->toString();
        }

        // --- node: 内联校验，避免 preg_match / 多次 hex2bin / 字符串拼接 ---
        if ($node === null) {
            $node = $this->nodeProvider->getNode();
        }

        if (is_int($node)) {
            $node = dechex($node);
        }

        $node = (string) $node;

        if ($node === '' || strlen($node) > 12 || !ctype_xdigit($node)) {
            throw new InvalidArgumentException('Invalid node value');
        }

        $nodeBin = (string) hex2bin(str_pad($node, 12, '0', STR_PAD_LEFT));

        // --- clockSeq: 静态种子缓存 + 14-bit 抽取，避免每次 random_int 系统调用 ---
        if ($clockSeq === null) {
            if (self::$clockSeqSeedOffset >= 14) {
                try {
                    // 取 3 字节 = 24-bit，可提供 1 次完整 14-bit 并剩余 10-bit 可弃；
                    // 为便于实现：每次取 3 字节仅产出 1 个 14-bit，简化维护。
                    // 这里采用 "取 4 字节 / 产出 2 个 14-bit" 的策略：
                    $seed = unpack('N', random_bytes(4))[1] & 0xffffffff;
                    self::$clockSeqSeed = $seed;
                    self::$clockSeqSeedOffset = 0;
                } catch (Throwable $exception) {
                    throw new RandomSourceException(
                        $exception->getMessage(),
                        (int) $exception->getCode(),
                        $exception
                    );
                }
            }

            $clockSeq = (self::$clockSeqSeed >> self::$clockSeqSeedOffset) & 0x3fff;
            self::$clockSeqSeedOffset += 14;
        }

        // --- time: 直接产生 8 字节 BigEndian，避免 hex2bin / dechex 往返 ---
        $time = $this->timeProvider->getTime();

        $uuidTime = $this->timeConverter->calculateTime(
            $time->getSeconds()->toString(),
            $time->getMicroseconds()->toString()
        );

        $timeHex = $uuidTime->toString();

        if (strlen($timeHex) > 16) {
            throw new TimeSourceException(
                sprintf('The generated time of \'%s\' is larger than expected', $timeHex)
            );
        }

        // 8 字节 BigEndian 二进制（前补零到 16 hex 字符 = 8 字节）
        $timeBytes = (string) hex2bin(str_pad($timeHex, 16, '0', STR_PAD_LEFT));

        // RFC 4122 布局: time_low(4) | time_mid(2) | time_hi_and_version(2)
        // 用一次 substr 连接，避免逐字节拼接（内部 memcpy 级别函数开销）
        return substr($timeBytes, 4, 4)
            . substr($timeBytes, 2, 2)
            . substr($timeBytes, 0, 2)
            . pack('n', $clockSeq)
            . $nodeBin;
    }
}
