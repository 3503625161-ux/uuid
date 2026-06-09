<?php

/**
 * This file is part of the ramsey/uuid library
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @copyright Copyright (c) Ben Ramsey <ben@ramsey.dev>
 * @license http://opensource.org/licenses/MIT MIT
 */

declare(strict_types=1);

namespace Ramsey\Uuid;

use DateTimeInterface;
use Ramsey\Uuid\Builder\UuidBuilderInterface;
use Ramsey\Uuid\Codec\CodecInterface;
use Ramsey\Uuid\Converter\NumberConverterInterface;
use Ramsey\Uuid\Converter\TimeConverterInterface;
use Ramsey\Uuid\Generator\DceSecurityGeneratorInterface;
use Ramsey\Uuid\Generator\DefaultTimeGenerator;
use Ramsey\Uuid\Generator\NameGeneratorInterface;
use Ramsey\Uuid\Generator\RandomGeneratorInterface;
use Ramsey\Uuid\Generator\TimeGeneratorInterface;
use Ramsey\Uuid\Generator\UnixTimeGenerator;
use Ramsey\Uuid\Lazy\LazyUuidFromString;
use Ramsey\Uuid\Provider\NodeProviderInterface;
use Ramsey\Uuid\Provider\Time\FixedTimeProvider;
use Ramsey\Uuid\Type\Hexadecimal;
use Ramsey\Uuid\Type\Integer as IntegerObject;
use Ramsey\Uuid\Type\Time;
use Ramsey\Uuid\Validator\ValidatorInterface;

use function bin2hex;
use function hex2bin;
use function pack;
use function str_pad;
use function strtolower;
use function substr;
use function substr_replace;
use function unpack;

use const STR_PAD_LEFT;

class UuidFactory implements UuidFactoryInterface
{
    private CodecInterface $codec;
    private DceSecurityGeneratorInterface $dceSecurityGenerator;
    private NameGeneratorInterface $nameGenerator;
    private NodeProviderInterface $nodeProvider;
    private NumberConverterInterface $numberConverter;
    private RandomGeneratorInterface $randomGenerator;
    private TimeConverterInterface $timeConverter;
    private TimeGeneratorInterface $timeGenerator;
    private TimeGeneratorInterface $unixTimeGenerator;
    private UuidBuilderInterface $uuidBuilder;
    private ValidatorInterface $validator;

    /**
     * @var bool whether the feature set was provided from outside, or we can operate under "default" assumptions
     */
    private bool $isDefaultFeatureSet;

    /**
     * @param FeatureSet | null $features A set of available features in the current environment
     */
    public function __construct(?FeatureSet $features = null)
    {
        $this->isDefaultFeatureSet = $features === null;

        $features ??= FeatureSetBuilder::fromDefaults()->build();

        $this->codec = $features->getCodec();
        $this->dceSecurityGenerator = $features->getDceSecurityGenerator();
        $this->nameGenerator = $features->getNameGenerator();
        $this->nodeProvider = $features->getNodeProvider();
        $this->numberConverter = $features->getNumberConverter();
        $this->randomGenerator = $features->getRandomGenerator();
        $this->timeConverter = $features->getTimeConverter();
        $this->timeGenerator = $features->getTimeGenerator();
        $this->uuidBuilder = $features->getBuilder();
        $this->validator = $features->getValidator();
        $this->unixTimeGenerator = $features->getUnixTimeGenerator();
    }

    /**
     * Returns the codec used by this factory
     */
    public function getCodec(): CodecInterface
    {
        return $this->codec;
    }

    /**
     * Sets the codec to use for this factory
     *
     * @param CodecInterface $codec A UUID encoder-decoder
     */
    public function setCodec(CodecInterface $codec): void
    {
        $this->isDefaultFeatureSet = false;

        $this->codec = $codec;
    }

    /**
     * Returns the name generator used by this factory
     */
    public function getNameGenerator(): NameGeneratorInterface
    {
        return $this->nameGenerator;
    }

    /**
     * Sets the name generator to use for this factory
     *
     * @param NameGeneratorInterface $nameGenerator A generator to generate binary data, based on a namespace and name
     */
    public function setNameGenerator(NameGeneratorInterface $nameGenerator): void
    {
        $this->isDefaultFeatureSet = false;

        $this->nameGenerator = $nameGenerator;
    }

    /**
     * Returns the node provider used by this factory
     */
    public function getNodeProvider(): NodeProviderInterface
    {
        return $this->nodeProvider;
    }

    /**
     * Returns the random generator used by this factory
     */
    public function getRandomGenerator(): RandomGeneratorInterface
    {
        return $this->randomGenerator;
    }

    /**
     * Sets the random generator to use for this factory
     *
     * @param RandomGeneratorInterface $randomGenerator A random source or generator of binary data
     */
    public function setRandomGenerator(RandomGeneratorInterface $randomGenerator): void
    {
        $this->isDefaultFeatureSet = false;

        $this->randomGenerator = $randomGenerator;
    }

    /**
     * Returns the time generator used by this factory
     */
    public function getTimeGenerator(): TimeGeneratorInterface
    {
        return $this->timeGenerator;
    }

    /**
     * Sets the time generator to use for this factory
     *
     * @param TimeGeneratorInterface $timeGenerator A time generator, used for generating version 1 UUIDs
     */
    public function setTimeGenerator(TimeGeneratorInterface $timeGenerator): void
    {
        $this->isDefaultFeatureSet = false;

        $this->timeGenerator = $timeGenerator;
    }

    /**
     * Returns the UNIX time generator used by this factory
     */
    public function getUnixTimeGenerator(): TimeGeneratorInterface
    {
        return $this->unixTimeGenerator;
    }

    /**
     * Sets the UNIX time generator to use for this factory
     *
     * @param TimeGeneratorInterface $unixTimeGenerator A time generator, used for generating version 7 UUIDs
     */
    public function setUnixTimeGenerator(TimeGeneratorInterface $unixTimeGenerator): void
    {
        $this->isDefaultFeatureSet = false;

        $this->unixTimeGenerator = $unixTimeGenerator;
    }

    /**
     * Returns the DCE Security generator used by this factory
     */
    public function getDceSecurityGenerator(): DceSecurityGeneratorInterface
    {
        return $this->dceSecurityGenerator;
    }

    /**
     * Sets the DCE Security generator to use for this factory
     *
     * @param DceSecurityGeneratorInterface $dceSecurityGenerator A DCE Security generator used for version 2 UUIDs
     */
    public function setDceSecurityGenerator(DceSecurityGeneratorInterface $dceSecurityGenerator): void
    {
        $this->isDefaultFeatureSet = false;

        $this->dceSecurityGenerator = $dceSecurityGenerator;
    }

    /**
     * Returns the number converter used by this factory
     */
    public function getNumberConverter(): NumberConverterInterface
    {
        return $this->numberConverter;
    }

    /**
     * Sets the number converter to use for this factory
     *
     * @param NumberConverterInterface $numberConverter A number converter used for converting hexadecimal UUID parts into integers
     */
    public function setNumberConverter(NumberConverterInterface $numberConverter): void
    {
        $this->isDefaultFeatureSet = false;

        $this->numberConverter = $numberConverter;
    }

    /**
     * Returns the UUID builder used by this factory
     */
    public function getUuidBuilder(): UuidBuilderInterface
    {
        return $this->uuidBuilder;
    }

    /**
     * Sets the UUID builder to use for this factory
     *
     * @param UuidBuilderInterface $builder A builder for constructing instances of UuidInterface
     */
    public function setUuidBuilder(UuidBuilderInterface $builder): void
    {
        $this->isDefaultFeatureSet = false;

        $this->uuidBuilder = $builder;
    }

    /**
     * Returns the validator used by this factory
     */
    public function getValidator(): ValidatorInterface
    {
        return $this->validator;
    }

    /**
     * Sets the validator to use for this factory
     */
    public function setValidator(ValidatorInterface $validator): void
    {
        $this->validator = $validator;
    }

    public function fromDateTime(DateTimeInterface $dateTime, ?Hexadecimal $node = null, ?int $clockSeq = null): UuidInterface
    {
        $timeProvider = new FixedTimeProvider(
            new Time((int) $dateTime->format('U'), (int) $dateTime->format('u'))
        );

        $timeGenerator = new DefaultTimeGenerator(
            $this->nodeProvider,
            $this->timeConverter,
            $timeProvider
        );

        $bytes = $timeGenerator->generate($node, $clockSeq);

        return $this->uuidFromBytesAndVersion($bytes, Uuid::UUID_TYPE_TIME);
    }

    public function fromString(string $uuid): UuidInterface
    {
        $uuid = strtolower($uuid);

        if (!($this->validator->validate($uuid))) {
            throw new Exception\InvalidUuidStringException(
                'Invalid UUID string: ' . $uuid
            );
        }

        return $this->fromBytes(hex2bin(str_replace(['urn:', 'uuid:', '{', '}', '-'], '', $uuid)));
    }

    public function fromBytes(string $bytes): UuidInterface
    {
        if (strlen($bytes) !== 16) {
            throw new Exception\InvalidArgumentException(
                '$bytes string should contain 16 characters.'
            );
        }

        return $this->uuidBuilder->build($this->codec, $bytes);
    }

    public function fromInteger(string $integer): UuidInterface
    {
        $hex = str_pad($this->numberConverter->toHex($integer), 32, '0', STR_PAD_LEFT);

        return $this->fromString($hex);
    }

    public function uuid1($node = null, ?int $clockSeq = null): UuidInterface
    {
        $bytes = $this->timeGenerator->generate($node, $clockSeq);

        return $this->uuidFromBytesAndVersion($bytes, Uuid::UUID_TYPE_TIME);
    }

    public function uuid2(int $localDomain, ?IntegerObject $localIdentifier = null, ?Hexadecimal $node = null, ?int $clockSeq = null): UuidInterface
    {
        $bytes = $this->dceSecurityGenerator->generate($localDomain, $localIdentifier, $node, $clockSeq);

        return $this->uuidFromBytesAndVersion($bytes, Uuid::UUID_TYPE_DCE_SECURITY);
    }

    public function uuid3(string|UuidInterface $ns, string $name): UuidInterface
    {
        return $this->uuidFromNsAndName($ns, $name, Uuid::UUID_TYPE_HASH_MD5);
    }

    public function uuid4(): UuidInterface
    {
        $bytes = $this->randomGenerator->generate(16);

        return $this->uuidFromBytesAndVersion($bytes, Uuid::UUID_TYPE_RANDOM);
    }

    public function uuid5(string|UuidInterface $ns, string $name): UuidInterface
    {
        return $this->uuidFromNsAndName($ns, $name, Uuid::UUID_TYPE_HASH_SHA1);
    }

    public function uuid6(?Hexadecimal $node = null, ?int $clockSeq = null): UuidInterface
    {
        $uuid = $this->uuid1($node, $clockSeq);

        return LazyUuidFromString::fromString($uuid->getFields()->toString());
    }

    public function uuid7(?DateTimeInterface $dateTime = null): UuidInterface
    {
        $bytes = $this->unixTimeGenerator->generate(null, null, $dateTime);

        return $this->uuidFromBytesAndVersion($bytes, Uuid::UUID_TYPE_UNIX_TIME);
    }

    public function uuid8(string $bytes): UuidInterface
    {
        return $this->uuidFromBytesAndVersion($bytes, Uuid::UUID_TYPE_CUSTOM);
    }

    private function uuidFromNsAndName(string|UuidInterface $ns, string $name, int $version): UuidInterface
    {
        if (!$ns instanceof UuidInterface) {
            $ns = $this->fromString($ns);
        }

        $bytes = $this->nameGenerator->generate($ns, $name, $version);

        return $this->uuidFromBytesAndVersion($bytes, $version);
    }

    private function uuidFromBytesAndVersion(string $bytes, int $version): UuidInterface
    {
        $unpackedTime = unpack('n*', substr($bytes, 6, 2));
        $timeHi = (int) $unpackedTime[1];
        $timeHiAndVersion = pack('n*', BinaryUtils::applyVersion($timeHi, $version));

        $unpackedClockSeq = unpack('n*', substr($bytes, 8, 2));
        $clockSeqHi = (int) $unpackedClockSeq[1];
        $clockSeqHiAndReserved = pack('n*', BinaryUtils::applyVariant($clockSeqHi));

        $bytes = substr_replace($bytes, $timeHiAndVersion, 6, 2);
        $bytes = substr_replace($bytes, $clockSeqHiAndReserved, 8, 2);

        if ($this->isDefaultFeatureSet && $version === Uuid::UUID_TYPE_CUSTOM) {
            return new LazyUuidFromString($this->codec->encodeBinary($bytes));
        }

        return $this->uuidBuilder->build($this->codec, $bytes);
    }
}
