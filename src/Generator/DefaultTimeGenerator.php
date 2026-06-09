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

use function is_int;
use function pack;
use function random_bytes;
use function sprintf;
use function str_pad;
use function strlen;
use function unpack;

use const PHP_INT_SIZE;
use const STR_PAD_LEFT;

/**
 * DefaultTimeGenerator generates strings of binary data based on a node ID, clock sequence, and the current time
 */
class DefaultTimeGenerator implements TimeGeneratorInterface
{
    private static int $clockSeq = -1;
    private static int $clockSeqSeedIndex = 0;
    private static array $clockSeqSeedParts = [];

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
        if ($node === null) {
            $node = $this->nodeProvider->getNode();
        }

        if ($node instanceof Hexadecimal) {
            $node = $node->toString();
        }

        if (is_int($node)) {
            if ($node < 0 || (PHP_INT_SIZE >= 8 && ($node >> 48) !== 0)) {
                throw new InvalidArgumentException('Invalid node value');
            }

            $node = pack('Nn', $node >> 16, $node & 0xffff);
        } else {
            $node = (string) $node;
            $nodeLength = strlen($node);

            if ($nodeLength === 0 || $nodeLength > 12) {
                throw new InvalidArgumentException('Invalid node value');
            }

            foreach (unpack('C*', $node) as $char) {
                if (($char < 48 || $char > 57) && ($char < 65 || $char > 70) && ($char < 97 || $char > 102)) {
                    throw new InvalidArgumentException('Invalid node value');
                }
            }

            $node = pack('H*', str_pad($node, 12, '0', STR_PAD_LEFT));
        }

        if ($clockSeq === null) {
            try {
                if (self::$clockSeqSeedIndex === 0) {
                    $seed = unpack('C*', random_bytes(65));

                    if (self::$clockSeq < 0) {
                        self::$clockSeq = (($seed[1] << 8) | $seed[2]) & 0x3fff;
                    }

                    self::$clockSeqSeedParts = [];

                    for ($i = 3, $part = 0; $i <= 65; $i += 3, $part++) {
                        self::$clockSeqSeedParts[$part] = ($seed[$i] << 16) | ($seed[$i + 1] << 8) | $seed[$i + 2];
                    }

                    self::$clockSeqSeedIndex = 21;
                }

                self::$clockSeq = (self::$clockSeq + 1 + self::$clockSeqSeedParts[--self::$clockSeqSeedIndex]) & 0x3fff;
                $clockSeq = self::$clockSeq;
            } catch (Throwable $exception) {
                throw new RandomSourceException($exception->getMessage(), (int) $exception->getCode(), $exception);
            }
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

        $time = unpack('n4', pack('H*', $timeHex));

        return pack('n5', $time[3], $time[4], $time[2], $time[1], $clockSeq) . $node;
    }
}
