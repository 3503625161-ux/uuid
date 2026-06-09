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
use Ramsey\Uuid\Generator\DceSecurityGenerator;
use Ramsey\Uuid\Generator\DceSecurityGeneratorInterface;
use Ramsey\Uuid\Generator\DefaultTimeGenerator;
use Ramsey\Uuid\Generator\NameGeneratorInterface;
use Ramsey\Uuid\Generator\RandomGeneratorInterface;
use Ramsey\Uuid\Generator\TimeGeneratorInterface;
use Ramsey\Uuid\Math\CalculatorInterface;
use Ramsey\Uuid\Provider\DceSecurityProviderInterface;
use Ramsey\Uuid\Provider\NodeProviderInterface;
use Ramsey\Uuid\Provider\TimeProviderInterface;
use Ramsey\Uuid\Validator\ValidatorInterface;

/**
 * FeatureSet is a pure value object that holds references to the components a UuidFactory should use.
 *
 * Environment probing and automatic component assembly no longer live here. These responsibilities
 * have moved to {@see StrategyResolver} (detection / selection) and {@see FeatureSetBuilder}
 * (construction). New code should obtain a configured {@see FeatureSet} through the builder (either
 * via its fluent API or the `fromDefaults()` factory).
 *
 * The {@see FeatureSet::__construct()} still supports the legacy positional boolean flags used by
 * existing callers (e.g. `new FeatureSet(true, true)`), but that usage is soft-deprecated.
 */
class FeatureSet
{
    private UuidBuilderInterface $builder;
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
    private ValidatorInterface $validator;

    /**
     * @param bool $useGuids True build UUIDs using the GuidStringCodec
     * @param bool $force32Bit True to force the use of 32-bit functionality
     * @param bool $ignoreSystemNode True to disable attempts to check for the system node ID
     * @param bool $enablePecl True to enable the use of the PeclUuidTimeGenerator to generate version 1 UUIDs
     */
    public function __construct(
        bool $useGuids = false,
        bool $force32Bit = false,
        bool $ignoreSystemNode = false,
        bool $enablePecl = false,
    ) {
        $blueprint = (new StrategyResolver($useGuids, $force32Bit, $ignoreSystemNode, $enablePecl))->resolve();

        $this->builder = $blueprint->builder;
        $this->calculator = $blueprint->calculator;
        $this->codec = $blueprint->codec;
        $this->dceSecurityGenerator = $blueprint->dceSecurityGenerator;
        $this->nameGenerator = $blueprint->nameGenerator;
        $this->nodeProvider = $blueprint->nodeProvider;
        $this->numberConverter = $blueprint->numberConverter;
        $this->randomGenerator = $blueprint->randomGenerator;
        $this->timeConverter = $blueprint->timeConverter;
        $this->timeGenerator = $blueprint->timeGenerator;
        $this->unixTimeGenerator = $blueprint->unixTimeGenerator;
        $this->validator = $blueprint->validator;
    }

    public function getBuilder(): UuidBuilderInterface
    {
        return $this->builder;
    }

    public function getCalculator(): CalculatorInterface
    {
        return $this->calculator;
    }

    public function getCodec(): CodecInterface
    {
        return $this->codec;
    }

    public function getDceSecurityGenerator(): DceSecurityGeneratorInterface
    {
        return $this->dceSecurityGenerator;
    }

    public function getNameGenerator(): NameGeneratorInterface
    {
        return $this->nameGenerator;
    }

    public function getNodeProvider(): NodeProviderInterface
    {
        return $this->nodeProvider;
    }

    public function getNumberConverter(): NumberConverterInterface
    {
        return $this->numberConverter;
    }

    public function getRandomGenerator(): RandomGeneratorInterface
    {
        return $this->randomGenerator;
    }

    public function getTimeConverter(): TimeConverterInterface
    {
        return $this->timeConverter;
    }

    public function getTimeGenerator(): TimeGeneratorInterface
    {
        return $this->timeGenerator;
    }

    public function getUnixTimeGenerator(): TimeGeneratorInterface
    {
        return $this->unixTimeGenerator;
    }

    public function getValidator(): ValidatorInterface
    {
        return $this->validator;
    }

    public function setBuilder(UuidBuilderInterface $builder): void
    {
        $this->builder = $builder;
    }

    public function setCalculator(CalculatorInterface $calculator): void
    {
        $this->calculator = $calculator;
    }

    public function setCodec(CodecInterface $codec): void
    {
        $this->codec = $codec;
    }

    public function setDceSecurityGenerator(DceSecurityGeneratorInterface $dceSecurityGenerator): void
    {
        $this->dceSecurityGenerator = $dceSecurityGenerator;
    }

    public function setNameGenerator(NameGeneratorInterface $nameGenerator): void
    {
        $this->nameGenerator = $nameGenerator;
    }

    public function setNodeProvider(NodeProviderInterface $nodeProvider): void
    {
        $this->nodeProvider = $nodeProvider;
    }

    public function setNumberConverter(NumberConverterInterface $numberConverter): void
    {
        $this->numberConverter = $numberConverter;
    }

    public function setRandomGenerator(RandomGeneratorInterface $randomGenerator): void
    {
        $this->randomGenerator = $randomGenerator;
    }

    public function setTimeConverter(TimeConverterInterface $timeConverter): void
    {
        $this->timeConverter = $timeConverter;
    }

    public function setTimeGenerator(TimeGeneratorInterface $timeGenerator): void
    {
        $this->timeGenerator = $timeGenerator;
    }

    public function setUnixTimeGenerator(TimeGeneratorInterface $unixTimeGenerator): void
    {
        $this->unixTimeGenerator = $unixTimeGenerator;
    }

    public function setValidator(ValidatorInterface $validator): void
    {
        $this->validator = $validator;
    }

    /**
     * Replaces the time provider and rebuilds the dependent time/DCE generators.
     *
     * Note: a fully explicit assembly using {@see FeatureSetBuilder} is preferred. This setter is
     * retained for backwards-compatible callers that replace the time provider on an existing
     * {@see FeatureSet}.
     */
    public function setTimeProvider(TimeProviderInterface $timeProvider): void
    {
        $this->timeGenerator = new DefaultTimeGenerator(
            $this->nodeProvider,
            $this->timeConverter,
            $timeProvider,
        );
        $this->dceSecurityGenerator = new DceSecurityGenerator(
            $this->numberConverter,
            $this->timeGenerator,
            $this->extractDceSecurityProvider($this->dceSecurityGenerator),
        );
    }

    /**
     * Replaces the DCE Security provider and rebuilds the dependent DCE generator.
     *
     * @see setTimeProvider() for the same note about the preferred builder API.
     */
    public function setDceSecurityProvider(DceSecurityProviderInterface $dceSecurityProvider): void
    {
        $this->dceSecurityGenerator = new DceSecurityGenerator(
            $this->numberConverter,
            $this->timeGenerator,
            $dceSecurityProvider,
        );
    }

    /**
     * Extracts the DceSecurityProviderInterface instance that was used to build the provided
     * DceSecurityGeneratorInterface. Because the concrete generator is constructed inside
     * StrategyResolver (or the legacy FeatureSet), we fall back to a fresh System provider when
     * introspection is not possible — this mirrors the original FeatureSet's default behaviour.
     */
    private function extractDceSecurityProvider(DceSecurityGeneratorInterface $generator): DceSecurityProviderInterface
    {
        if ($generator instanceof DceSecurityGenerator) {
            $reflect = new \ReflectionObject($generator);

            if ($reflect->hasProperty('dceSecurityProvider')) {
                $prop = $reflect->getProperty('dceSecurityProvider');
                $prop->setAccessible(true);

                $value = $prop->getValue($generator);

                if ($value instanceof DceSecurityProviderInterface) {
                    return $value;
                }
            }
        }

        return new \Ramsey\Uuid\Provider\Dce\SystemDceSecurityProvider();
    }
}
