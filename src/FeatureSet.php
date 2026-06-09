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

namespace Ramsey\Uuid;

use Ramsey\Uuid\Builder\UuidBuilderInterface;
use Ramsey\Uuid\Codec\CodecInterface;
use Ramsey\Uuid\Converter\NumberConverterInterface;
use Ramsey\Uuid\Converter\TimeConverterInterface;
use Ramsey\Uuid\Generator\DceSecurityGeneratorInterface;
use Ramsey\Uuid\Generator\NameGeneratorInterface;
use Ramsey\Uuid\Generator\RandomGeneratorInterface;
use Ramsey\Uuid\Generator\TimeGeneratorInterface;
use Ramsey\Uuid\Math\CalculatorInterface;
use Ramsey\Uuid\Provider\DceSecurityProviderInterface;
use Ramsey\Uuid\Provider\NodeProviderInterface;
use Ramsey\Uuid\Provider\TimeProviderInterface;
use Ramsey\Uuid\Validator\ValidatorInterface;

/**
 * FeatureSet is a pure value-object container that holds component instances
 *
 * All environment detection and assembly logic has been extracted to
 * StrategyResolver and FeatureSetBuilder. FeatureSet now only holds
 * references to its component instances and provides getters for them.
 *
 * For backward compatibility, the legacy constructor delegates to
 * FeatureSetBuilder::fromDefaults().
 */
class FeatureSet
{
    private ?TimeProviderInterface $timeProvider;
    private CalculatorInterface $calculator;
    private CodecInterface $codec;
    private DceSecurityGeneratorInterface $dceSecurityGenerator;
    private NameGeneratorInterface $nameGenerator;
    private NodeProviderInterface $nodeProvider;
    private NumberConverterInterface $numberConverter;
    private RandomGeneratorInterface $randomGenerator;
    private TimeConverterInterface $timeConverter;
    private TimeGeneratorInterface $timeGenerator;
    private TimeGeneratorInterface $unixTimeGenerator;
    private UuidBuilderInterface $builder;
    private ValidatorInterface $validator;

    private bool $force32Bit;
    private bool $ignoreSystemNode;
    private bool $enablePecl;

    /**
     * @param bool $useGuids True build UUIDs using the GuidStringCodec
     * @param bool $force32Bit True to force the use of 32-bit functionality (primarily for testing purposes)
     * @param bool $forceNoBigNumber (obsolete)
     * @param bool $ignoreSystemNode True to disable attempts to check for the system node ID (primarily for testing purposes)
     * @param bool $enablePecl True to enable the use of the PeclUuidTimeGenerator to generate version 1 UUIDs
     *
     * @phpstan-ignore constructor.unusedParameter ($forceNoBigNumber is deprecated)
     */
    public function __construct(
        bool $useGuids = false,
        bool $force32Bit = false,
        bool $forceNoBigNumber = false,
        bool $ignoreSystemNode = false,
        bool $enablePecl = false,
    ) {
        $this->force32Bit = $force32Bit;
        $this->ignoreSystemNode = $ignoreSystemNode;
        $this->enablePecl = $enablePecl;

        $featureSet = FeatureSetBuilder::fromDefaults($useGuids, $force32Bit, $ignoreSystemNode, $enablePecl);

        $this->timeProvider = $featureSet->getTimeProvider();
        $this->calculator = $featureSet->getCalculator();
        $this->codec = $featureSet->getCodec();
        $this->dceSecurityGenerator = $featureSet->getDceSecurityGenerator();
        $this->nameGenerator = $featureSet->getNameGenerator();
        $this->nodeProvider = $featureSet->getNodeProvider();
        $this->numberConverter = $featureSet->getNumberConverter();
        $this->randomGenerator = $featureSet->getRandomGenerator();
        $this->timeConverter = $featureSet->getTimeConverter();
        $this->timeGenerator = $featureSet->getTimeGenerator();
        $this->unixTimeGenerator = $featureSet->getUnixTimeGenerator();
        $this->builder = $featureSet->getBuilder();
        $this->validator = $featureSet->getValidator();
    }

    /**
     * Creates a FeatureSet from explicitly provided component instances
     *
     * This factory method is the "pure" creation path used by FeatureSetBuilder.
     * It directly sets all properties without any environment detection or
     * assembly logic.
     *
     * @internal
     */
    public static function fromComponents(
        CalculatorInterface $calculator,
        NumberConverterInterface $numberConverter,
        TimeConverterInterface $timeConverter,
        RandomGeneratorInterface $randomGenerator,
        TimeGeneratorInterface $timeGenerator,
        TimeGeneratorInterface $unixTimeGenerator,
        NameGeneratorInterface $nameGenerator,
        NodeProviderInterface $nodeProvider,
        UuidBuilderInterface $builder,
        CodecInterface $codec,
        DceSecurityGeneratorInterface $dceSecurityGenerator,
        ?TimeProviderInterface $timeProvider,
        ValidatorInterface $validator,
    ): self {
        /** @phpstan-ignore new.constructor (PHPStan doesn't see we bypass constructor via unserialize) */
        $instance = (new \ReflectionClass(self::class))->newInstanceWithoutConstructor();

        $instance->force32Bit = false;
        $instance->ignoreSystemNode = false;
        $instance->enablePecl = false;
        $instance->calculator = $calculator;
        $instance->numberConverter = $numberConverter;
        $instance->timeConverter = $timeConverter;
        $instance->randomGenerator = $randomGenerator;
        $instance->timeGenerator = $timeGenerator;
        $instance->unixTimeGenerator = $unixTimeGenerator;
        $instance->nameGenerator = $nameGenerator;
        $instance->nodeProvider = $nodeProvider;
        $instance->builder = $builder;
        $instance->codec = $codec;
        $instance->dceSecurityGenerator = $dceSecurityGenerator;
        $instance->timeProvider = $timeProvider;
        $instance->validator = $validator;

        return $instance;
    }

    /**
     * Returns the builder configured for this environment
     */
    public function getBuilder(): UuidBuilderInterface
    {
        return $this->builder;
    }

    /**
     * Returns the calculator configured for this environment
     */
    public function getCalculator(): CalculatorInterface
    {
        return $this->calculator;
    }

    /**
     * Returns the codec configured for this environment
     */
    public function getCodec(): CodecInterface
    {
        return $this->codec;
    }

    /**
     * Returns the DCE Security generator configured for this environment
     */
    public function getDceSecurityGenerator(): DceSecurityGeneratorInterface
    {
        return $this->dceSecurityGenerator;
    }

    /**
     * Returns the name generator configured for this environment
     */
    public function getNameGenerator(): NameGeneratorInterface
    {
        return $this->nameGenerator;
    }

    /**
     * Returns the node provider configured for this environment
     */
    public function getNodeProvider(): NodeProviderInterface
    {
        return $this->nodeProvider;
    }

    /**
     * Returns the number converter configured for this environment
     */
    public function getNumberConverter(): NumberConverterInterface
    {
        return $this->numberConverter;
    }

    /**
     * Returns the random generator configured for this environment
     */
    public function getRandomGenerator(): RandomGeneratorInterface
    {
        return $this->randomGenerator;
    }

    /**
     * Returns the time converter configured for this environment
     */
    public function getTimeConverter(): TimeConverterInterface
    {
        return $this->timeConverter;
    }

    /**
     * Returns the time generator configured for this environment
     */
    public function getTimeGenerator(): TimeGeneratorInterface
    {
        return $this->timeGenerator;
    }

    /**
     * Returns the Unix Epoch time generator configured for this environment
     */
    public function getUnixTimeGenerator(): TimeGeneratorInterface
    {
        return $this->unixTimeGenerator;
    }

    /**
     * Returns the validator configured for this environment
     */
    public function getValidator(): ValidatorInterface
    {
        return $this->validator;
    }

    /**
     * Returns the time provider configured for this environment
     */
    public function getTimeProvider(): ?TimeProviderInterface
    {
        return $this->timeProvider;
    }

    /**
     * Sets the calculator to use in this environment
     */
    public function setCalculator(CalculatorInterface $calculator): void
    {
        $this->calculator = $calculator;

        $resolver = new StrategyResolver($this->force32Bit);
        $this->numberConverter = $resolver->resolveNumberConverter($calculator);
        $this->timeConverter = $resolver->resolveTimeConverter($calculator);

        if (isset($this->timeProvider)) {
            $this->timeGenerator = $resolver->resolveTimeGenerator(
                $this->enablePecl,
                $this->nodeProvider,
                $this->timeConverter,
                $this->timeProvider,
            );
        }
    }

    /**
     * Sets the DCE Security provider to use in this environment
     */
    public function setDceSecurityProvider(DceSecurityProviderInterface $dceSecurityProvider): void
    {
        $resolver = new StrategyResolver($this->force32Bit);
        $this->dceSecurityGenerator = $resolver->resolveDceSecurityGenerator(
            $this->numberConverter,
            $this->timeGenerator,
            $dceSecurityProvider,
        );
    }

    /**
     * Sets the node provider to use in this environment
     */
    public function setNodeProvider(NodeProviderInterface $nodeProvider): void
    {
        $this->nodeProvider = $nodeProvider;

        if (isset($this->timeProvider)) {
            $resolver = new StrategyResolver($this->force32Bit);
            $this->timeGenerator = $resolver->resolveTimeGenerator(
                $this->enablePecl,
                $this->nodeProvider,
                $this->timeConverter,
                $this->timeProvider,
            );
        }
    }

    /**
     * Sets the time provider to use in this environment
     */
    public function setTimeProvider(TimeProviderInterface $timeProvider): void
    {
        $this->timeProvider = $timeProvider;

        $resolver = new StrategyResolver($this->force32Bit);
        $this->timeGenerator = $resolver->resolveTimeGenerator(
            $this->enablePecl,
            $this->nodeProvider,
            $this->timeConverter,
            $timeProvider,
        );
    }

    /**
     * Set the validator to use in this environment
     */
    public function setValidator(ValidatorInterface $validator): void
    {
        $this->validator = $validator;
    }
}