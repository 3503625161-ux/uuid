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

use function hash;
use function hex2bin;
use function is_int;
use function pack;
use function random_bytes;
use function sprintf;
use function str_pad;
use function strlen;
use function unpack;

use const STR_PAD_LEFT;

/**
 * DefaultTimeGenerator generates strings of binary data based on a node ID, clock sequence, and the current time
 */
class DefaultTimeGenerator implements TimeGeneratorInterface
{
    private static string $clockSeqSeed = '';
    private static int $clockSeqIndex = 0;
    private static int $clockSeqCounter = 0;
    /** @var int[] */
    private static array $clockSeqParts = [];

    public function __construct(
        private NodeProviderInterface $nodeProvider,
        private TimeConverterInterface $timeConverter,
        private TimeProviderInterface $timeProvider,
    ) {
    }

    /**
     * @throws InvalidArgumentException if the parameters contain invalid values
     * @throws RandomSourceException if random_int() throws an exception/error
     *
     * @inheritDoc
     */
    public function generate($node = null, ?int $clockSeq = null): string
    {
        if ($node instanceof Hexadecimal) {
            $node = $node->toString();
        }

        if ($node === null) {
            $node = $this->nodeProvider->getNode();
        }

        if (is_int($node)) {
            $nodeBin = pack('n', ($node >> 32) & 0xffff) . pack('N', $node & 0xffffffff);
        } else {
            $nodeBin = hex2bin(str_pad((string) $node, 12, '0', STR_PAD_LEFT));
            if ($nodeBin === false) {
                throw new InvalidArgumentException('Invalid node value');
            }
        }

        if ($clockSeq === null) {
            if (self::$clockSeqIndex === 0) {
                if (self::$clockSeqSeed === '') {
                    try {
                        self::$clockSeqSeed = random_bytes(16);
                    } catch (Throwable $exception) {
                        throw new RandomSourceException($exception->getMessage(), (int) $exception->getCode(), $exception);
                    }
                } else {
                    self::$clockSeqSeed = hash('sha512', self::$clockSeqSeed, true);
                }

                /** @var int[] $s */
                $s = unpack('l*', self::$clockSeqSeed);
                $s[] = ($s[1] >> 8 & 0xff0000) | ($s[2] >> 16 & 0xff00) | ($s[3] >> 24 & 0xff);
                $s[] = ($s[4] >> 8 & 0xff0000) | ($s[5] >> 16 & 0xff00) | ($s[6] >> 24 & 0xff);
                $s[] = ($s[7] >> 8 & 0xff0000) | ($s[8] >> 16 & 0xff00) | ($s[9] >> 24 & 0xff);
                $s[] = ($s[10] >> 8 & 0xff0000) | ($s[11] >> 16 & 0xff00) | ($s[12] >> 24 & 0xff);
                $s[] = ($s[13] >> 8 & 0xff0000) | ($s[14] >> 16 & 0xff00) | ($s[15] >> 24 & 0xff);

                self::$clockSeqParts = $s;
                self::$clockSeqIndex = 21;
            }

            $clockSeq = (self::$clockSeqParts[--self::$clockSeqIndex] + self::$clockSeqCounter++) & 0x3fff;
        }

        $time = $this->timeProvider->getTime();

        $uuidTime = $this->timeConverter->calculateTime(
            $time->getSeconds()->toString(),
            $time->getMicroseconds()->toString()
        );

        $timeHex = str_pad($uuidTime->toString(), 16, '0', STR_PAD_LEFT);

        if (strlen($timeHex) !== 16) {
            throw new TimeSourceException(sprintf('The generated time of \'%s\' is larger than expected', $timeHex));
        }

        $timeBytes = (string) hex2bin($timeHex);
        ['hi' => $hi, 'lo' => $lo] = unpack('Nhi/Nlo', $timeBytes);

        return pack('Nnnn', $lo, ($hi >> 16) & 0xffff, $hi & 0xffff, $clockSeq) . $nodeBin;
    }
