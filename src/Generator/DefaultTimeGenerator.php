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
use function unpack;

use const STR_PAD_LEFT;

/**
 * DefaultTimeGenerator generates strings of binary data based on a node ID, clock sequence, and the current time
 */
class DefaultTimeGenerator implements TimeGeneratorInterface
{
    private static string $clockSeqSeed = '';
    private static int $clockSeqIndex = 0;
    /** @var int[] */
    private static array $clockSeqPool = [];

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

        $node = $this->getValidNode($node);

        if ($clockSeq === null) {
            if (self::$clockSeqIndex === 0) {
                try {
                    self::$clockSeqSeed = random_bytes(10);
                    /** @var int[] $pool */
                    $pool = unpack('n*', self::$clockSeqSeed);
                    foreach ($pool as &$v) {
                        $v &= 0x3fff;
                    }
                    unset($v);
                    self::$clockSeqPool = $pool;
                    self::$clockSeqIndex = 5;
                } catch (Throwable $exception) {
                    throw new RandomSourceException($exception->getMessage(), (int) $exception->getCode(), $exception);
                }
            }
            $clockSeq = self::$clockSeqPool[--self::$clockSeqIndex];
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

        return pack('Nnnn', $lo, ($hi >> 16) & 0xffff, $hi & 0xffff, $clockSeq) . $node;
    }

    /**
     * Uses the node provider given when constructing this instance to get the node ID (usually a MAC address)
     *
     * @param int | string | null $node A node value that may be used to override the node provider
     *
     * @return string 6-byte binary string representation of the node
     *
     * @throws InvalidArgumentException
     */
    private function getValidNode(int | string | null $node): string
    {
        if ($node === null) {
            $node = $this->nodeProvider->getNode();
        }

        // Convert the node to hex if it is still an integer.
        if (is_int($node)) {
            $node = dechex($node);
        }

        if (!ctype_xdigit((string) $node) || strlen((string) $node) > 12) {
            throw new InvalidArgumentException('Invalid node value');
        }

        return (string) hex2bin(str_pad((string) $node, 12, '0', STR_PAD_LEFT));
    }
}
