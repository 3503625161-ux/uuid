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
use function hexdec;
use function is_int;
use function pack;
use function random_bytes;
use function sprintf;
use function str_pad;
use function strlen;
use function strtolower;
use function unpack;

use const STR_PAD_LEFT;

/**
 * DefaultTimeGenerator generates strings of binary data based on a node ID, clock sequence, and the current time
 */
class DefaultTimeGenerator implements TimeGeneratorInterface
{
    private static ?string $seed = null;
    private static int $seedIndex = 0;

    /** @var int[] */
    private static array $seedParts = [];

    private static int $clockSeq = 0;

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
        } elseif ($node === null) {
            $node = $this->nodeProvider->getNode();
        }

        if (is_int($node)) {
            $nodeBytes = pack('nN', ($node >> 32) & 0xffff, $node & 0xffffffff);
        } else {
            $nodeStr = (string) $node;
            if (strlen($nodeStr) > 12) {
                throw new InvalidArgumentException('Invalid node value');
            }
            $nodeInt = hexdec($nodeStr);
            $nodeBytes = pack('nN', ($nodeInt >> 32) & 0xffff, $nodeInt & 0xffffffff);
            if (unpack('H*', $nodeBytes)[1] !== str_pad(strtolower($nodeStr), 12, '0', STR_PAD_LEFT)) {
                throw new InvalidArgumentException('Invalid node value');
            }
        }

        if ($clockSeq === null) {
            if (self::$seedIndex === 0) {
                if (self::$seed === null) {
                    try {
                        self::$seed = random_bytes(16);
                    } catch (Throwable $exception) {
                        throw new RandomSourceException($exception->getMessage(), (int) $exception->getCode(), $exception);
                    }
                    self::$clockSeq = unpack('n', self::$seed)[1] & 0x3fff;
                }
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

            self::$clockSeq = (self::$clockSeq + (self::$seedParts[self::$seedIndex--] & 0xffffff)) & 0x3fff;
            $clockSeq = self::$clockSeq;
        }

        $time = $this->timeProvider->getTime();

        $uuidTime = $this->timeConverter->calculateTime(
            $time->getSeconds()->toString(),
            $time->getMicroseconds()->toString()
        );

        $timeHex = $uuidTime->toString();

        if (strlen($timeHex) > 16) {
            throw new TimeSourceException(sprintf('The generated time of \'%s\' is larger than expected', str_pad($timeHex, 16, '0', STR_PAD_LEFT)));
        }

        $timeInt = hexdec($timeHex);

        return pack(
            'Nnnn',
            $timeInt & 0xffffffff,
            ($timeInt >> 32) & 0xffff,
            ($timeInt >> 48) & 0xffff,
            $clockSeq
        ) . $nodeBytes;
    }
}
