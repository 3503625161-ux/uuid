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
use Ramsey\Uuid\Provider\NodeProviderInterface;
use Ramsey\Uuid\Validator\ValidatorInterface;

/**
 * FeatureSetBuilder constructs a {@see FeatureSet} using an explicit, fluent API
 *
 * The builder starts from an optional {@see StrategyResolver} blueprint, which picks default
 * implementations based on the runtime environment. Callers can then override any component
 * individually using the `with*` methods, and finally invoke {@see FeatureSetBuilder::build()} to
 * obtain a fully configured {@see FeatureSet}.
 *
 * Typical usage — default assembly:
 *
 * ```php
 * $features = FeatureSetBuilder::fromDefaults()->build();
 * ```
 *
 * Typical usage — selective overrides:
 *
 * ```php
 * $features = FeatureSetBuilder::fromDefaults()
 *     ->withCalculator(new MyCustomCalculator())
 *     ->withNodeProvider(new MyCustomNodeProvider())
 *     ->build();
 * ```
 *
 * Typical usage — fully explicit construction:
 *
 * ```php
 * $features = (new FeatureSetBuilder())
 *     ->withBuilder($builder)
 *     ->withCalculator($calculator)
 *     ->withCodec($codec)
 *     // ... etc for each component
 *     ->build();
 * ```
 */
final class FeatureSetBuilder
{
    private ?UuidBuilderInterface $builder = null;
    private ?CalculatorInterface $calculator = null;
    private ?CodecInterface $codec = null;
    private ?DceSecurityGeneratorInterface $dceSecurityGenerator = null;
    private ?NameGeneratorInterface $nameGenerator = null;
    private ?NodeProviderInterface $nodeProvider = null;
    private ?NumberConverterInterface $numberConverter = null;
    private ?RandomGeneratorInterface $randomGenerator = null;
    private ?TimeConverterInterface $timeConverter = null;
    private ?TimeGeneratorInterface $timeGenerator = null;
    private ?TimeGeneratorInterface $unixTimeGenerator = null;
    private ?ValidatorInterface $validator = null;

    /**
     * Returns a builder pre-populated with the best implementations for the current environment
     *
     * Environment detection is delegated to {@see StrategyResolver}, using the provided flags to
     * tweak the selection strategy (GUID mode, 32-bit forcing, PECL uuid extension, etc.).
     */
    public static function fromDefaults(
        bool $useGuids = false,
        bool $force32Bit = false,
        bool $ignoreSystemNode = false,
        bool $enablePecl = false,
    ): self {
        $blueprint = (new StrategyResolver($useGuids, $force32Bit, $ignoreSystemNode, $enablePecl))->resolve();

        $builder = new self();
        $builder->builder = $blueprint->builder;
        $builder->calculator = $blueprint->calculator;
        $builder->codec = $blueprint->codec;
        $builder->dceSecurityGenerator = $blueprint->dceSecurityGenerator;
        $builder->nameGenerator = $blueprint->nameGenerator;
        $builder->nodeProvider = $blueprint->nodeProvider;
        $builder->numberConverter = $blueprint->numberConverter;
        $builder->randomGenerator = $blueprint->randomGenerator;
        $builder->timeConverter = $blueprint->timeConverter;
        $builder->timeGenerator = $blueprint->timeGenerator;
        $builder->unixTimeGenerator = $blueprint->unixTimeGenerator;
        $builder->validator = $blueprint->validator;

        return $builder;
    }

    public function withBuilder(UuidBuilderInterface $builder): self
    {
        $this->builder = $builder;

        return $this;
    }

    public function withCalculator(CalculatorInterface $calculator): self
    {
        $this->calculator = $calculator;

        return $this;
    }

    public function withCodec(CodecInterface $codec): self
    {
        $this->codec = $codec;

        return $this;
    }

    public function withDceSecurityGenerator(DceSecurityGeneratorInterface $dceSecurityGenerator): self
    {
        $this->dceSecurityGenerator = $dceSecurityGenerator;

        return $this;
    }

    public function withNameGenerator(NameGeneratorInterface $nameGenerator): self
    {
        $this->nameGenerator = $nameGenerator;

        return $this;
    }

    public function withNodeProvider(NodeProviderInterface $nodeProvider): self
    {
        $this->nodeProvider = $nodeProvider;

        return $this;
    }

    public function withNumberConverter(NumberConverterInterface $numberConverter): self
    {
        $this->numberConverter = $numberConverter;

        return $this;
    }

    public function withRandomGenerator(RandomGeneratorInterface $randomGenerator): self
    {
        $this->randomGenerator = $randomGenerator;

        return $this;
    }

    public function withTimeConverter(TimeConverterInterface $timeConverter): self
    {
        $this->timeConverter = $timeConverter;

        return $this;
    }

    public function withTimeGenerator(TimeGeneratorInterface $timeGenerator): self
    {
        $this->timeGenerator = $timeGenerator;

        return $this;
    }

    public function withUnixTimeGenerator(TimeGeneratorInterface $unixTimeGenerator): self
    {
        $this->unixTimeGenerator = $unixTimeGenerator;

        return $this;
    }

    public function withValidator(ValidatorInterface $validator): self
    {
        $this->validator = $validator;

        return $this;
    }

    /**
     * Builds and returns the configured {@see FeatureSet}
     *
     * @throws \LogicException if any required component has not been provided or resolved yet.
     */
    public function build(): FeatureSet
    {
        $missing = [];

        if ($this->builder === null) {
            $missing[] = 'builder';
        }

        if ($this->calculator === null) {
            $missing[] = 'calculator';
        }

        if ($this->codec === null) {
            $missing[] = 'codec';
        }

        if ($this->dceSecurityGenerator === null) {
            $missing[] = 'dceSecurityGenerator';
        }

        if ($this->nameGenerator === null) {
            $missing[] = 'nameGenerator';
        }

        if ($this->nodeProvider === null) {
            $missing[] = 'nodeProvider';
        }

        if ($this->numberConverter === null) {
            $missing[] = 'numberConverter';
        }

        if ($this->randomGenerator === null) {
            $missing[] = 'randomGenerator';
        }

        if ($this->timeConverter === null) {
            $missing[] = 'timeConverter';
        }

        if ($this->timeGenerator === null) {
            $missing[] = 'timeGenerator';
        }

        if ($this->unixTimeGenerator === null) {
            $missing[] = 'unixTimeGenerator';
        }

        if ($this->validator === null) {
            $missing[] = 'validator';
        }

        if ($missing !== []) {
            throw new \LogicException(
                'Cannot build FeatureSet: missing component(s) ' . implode(', ', $missing) . '. '
                . 'Either provide them explicitly using the with*() methods, or start from '
                . 'FeatureSetBuilder::fromDefaults() to let StrategyResolver pick defaults.',
            );
        }

        $featureSet = new FeatureSet();
        $featureSet->setBuilder($this->builder);
        $featureSet->setCalculator($this->calculator);
        $featureSet->setCodec($this->codec);
        $featureSet->setDceSecurityGenerator($this->dceSecurityGenerator);
        $featureSet->setNameGenerator($this->nameGenerator);
        $featureSet->setNodeProvider($this->nodeProvider);
        $featureSet->setNumberConverter($this->numberConverter);
        $featureSet->setRandomGenerator($this->randomGenerator);
        $featureSet->setTimeConverter($this->timeConverter);
        $featureSet->setTimeGenerator($this->timeGenerator);
        $featureSet->setUnixTimeGenerator($this->unixTimeGenerator);
        $featureSet->setValidator($this->validator);

        return $featureSet;
    }
}
