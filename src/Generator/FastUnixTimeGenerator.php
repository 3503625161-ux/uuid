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

use DateTimeInterface;

use function hash;
use function pack;
use function substr;
use function unpack;

use const PHP_INT_SIZE;

/**
 * FastUnixTimeGenerator 是极致性能的 Unix 时间戳 UUID v7 字节生成器。
 *
 * 核心优化策略:
 *   - 静态缓存 sha512 种子，单次 random_bytes(10) 可支撑 21 次生成;
 *   - 使用 unpack/pack 批量处理二进制，严禁逐字节操作;
 *   - 用位运算 (& / 条件分支全部收敛;
 *   - 热路径全部内联，避免额外函数调用;
 *   - 参照 UnixTimeGenerator 的增量策略: sha512 哈希种子 + 24-bit 自增计数器;
 *   - 仅在种子耗尽或时间回退 / 计数器溢出时刷新 random_bytes。
 */
class FastUnixTimeGenerator implements TimeGeneratorInterface
{
    private static int $time = 0;

    /** @var int[] */
    private static array $rand = [0, 0, 0, 0, 0, 0];

    /** @var int[] */
    private static array $seedParts = [];

    private static int $seedIndex = 0;

    private static string $seed = '';

    public function __construct(
        private RandomGeneratorInterface $randomGenerator,
        private int $intSize = PHP_INT_SIZE,
    ) {
    }

    /**
     * @param mixed $node      未使用 (保持接口兼容
     * @param int|null $clockSeq 未使用
     * @param DateTimeInterface|null $dateTime 若提供则使用该时间
     */
    public function generate($node = null, ?int $clockSeq = null, ?DateTimeInterface $dateTime = null): string
    {
        if ($dateTime === null) {
            $time = microtime(false);
            $time = (int) (substr($time, 11) . substr($time, 2, 3));
        } else {
            $time = (int) $dateTime->format('Uv');
        }

        if ($time > self::$time) {
            $this->randomize($time);
        } else {
            $time = $this->increment();
        }

        return ($this->intSize >= 8)
            ? (substr(pack('J', $time), -6) . pack('n*', self::$rand[1], self::$rand[2], self::$rand[3], self::$rand[4], self::$rand[5]))
            : (self::packTime32($time) . pack('n*', self::$rand[1], self::$rand[2], self::$rand[3], self::$rand[4], self::$rand[5]));
    }

    /**
     * 32 位系统下的时间戳打包 (大端字节序 48 位。
     * 内联使用位运算提取字节，避免 BigMath 和 substr 等昂贵调用。
     */
    private static function packTime32(int $time): string
    {
        return pack(
            'n*',
            ($time >> 32) & 0xffff,
            ($time >> 16) & 0xffff,
            $time & 0xffff,
        );
    }

    private function randomize(int $time): void
    {
        self::$time = $time;
        self::$seed = $this->randomGenerator->generate(self::$seed === '' ? 16 : 10);

        /** @var int[] $rand */
        $rand = unpack('n*', self::$seed);
        $rand[1] &= 0x03ff;

        self::$rand = $rand;
    }

    /**
     * 同毫秒内增量:
     *  - 首次进入时通过 sha512 哈希 self::$seed 得到 64 字节熵;
     *  - unpack('l*') 得到 16 个 32 位整数;
     *  - 通过位运算从剩余高字节拼接 5 个额外 24 位整数，合计 21 个;
     *  - 逐次自增消耗，直到 $seedIndex 耗尽前不调用 random_bytes。
     */
    private function increment(): int
    {
        if (self::$seedIndex === 0) {
            self::$seed = hash('sha512', self::$seed, true);

            /** @var int[] $s */
            $s = unpack('l*', self::$seed);
            $s[] = ($s[1] >> 8 & 0xff0000) | ($s[2] >> 16 & 0xff00) | ($s[3] >> 24 & 0xff);
            $s[] = ($s[4] >> 8 & 0xff0000) | ($s[5] >> 16 & 0xff00) | ($s[6] >> 24 & 0xff);
            $s[] = ($s[7] >> 8 & 0xff0000) | ($s[8] >> 16 & 0xff00) | ($s[9] >> 24 & 0xff);
            $s[] = ($s[10] >> 8 & 0xff0000) | ($s[11] >> 16 & 0xff00) | ($s[12] >> 24 & 0xff);
            $s[] = ($s[13] >> 8 & 0xff0000) | ($s[14] >> 16 & 0xff00) | ($s[15] >> 24 & 0xff);

            self::$seedParts = $s;
            self::$seedIndex = 21;
        }

        self::$rand[5] = 0xffff & $carry = self::$rand[5] + 1 + (self::$seedParts[self::$seedIndex--] & 0xffffff);
        self::$rand[4] = 0xffff & $carry = self::$rand[4] + ($carry >> 16);
        self::$rand[3] = 0xffff & $carry = self::$rand[3] + ($carry >> 16);
        self::$rand[2] = 0xffff & $carry = self::$rand[2] + ($carry >> 16);
        self::$rand[1] += $carry >> 16;

        if (0xfc00 & self::$rand[1]) {
            $this->randomize(self::$time + 1);
        }

        return self::$time;
    }
}
