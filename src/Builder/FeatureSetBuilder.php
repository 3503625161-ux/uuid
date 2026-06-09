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

namespace Ramsey\Uuid\Builder;

use Ramsey\Uuid\Codec\CodecInterface;
use Ramsey\Uuid\Converter\NumberConverterInterface;
use Ramsey\Uuid\Converter\TimeConverterInterface;
use Ramsey\Uuid\FeatureSet;
use Ramsey\Uuid\Generator\DceSecurityGeneratorInterface;
use Ramsey\Uuid\Generator\NameGeneratorInterface;
use Ramsey\Uuid\Generator\RandomGeneratorInterface;
use Ramsey\Uuid\Generator\TimeGeneratorInterface;
use Ramsey\Uuid\Math\CalculatorInterface;
use Ramsey\Uuid\Provider\NodeProviderInterface;
use Ramsey\Uuid\Provider\TimeProviderInterface;
use Ramsey\Uuid\Validator\ValidatorInterface;

class FeatureSetBuilder
{
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
    private ?UuidBuilderInterface $builder = null;
    private ?ValidatorInterface $validator = null;

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

    public function withTimeProvider(TimeProviderInterface $timeProvider): self
    {
        if ($this->nodeProvider !== null && $this->timeConverter !== null) {
            $this->timeGenerator = (new \Ramsey\Uuid\Generator\TimeGeneratorFactory(
                $this->nodeProvider,
                $this->timeConverter,
                $timeProvider
            ))->getGenerator();
        }
        return $this;
    }

    public function withUnixTimeGenerator(TimeGeneratorInterface $unixTimeGenerator): self
    {
        $this->unixTimeGenerator = $unixTimeGenerator;
        return $this;
    }

    public function withBuilder(UuidBuilderInterface $builder): self
    {
        $this->builder = $builder;
        return $this;
    }

    public function withValidator(ValidatorInterface $validator): self
    {
        $this->validator = $validator;
        return $this;
    }

    /**
     * Builds and returns the FeatureSet instance.
     */
    public function build(): FeatureSet
    {
        return new FeatureSet(
            $this->calculator,
            $this->codec,
            $this->dceSecurityGenerator,
            $this->nameGenerator,
            $this->nodeProvider,
            $this->numberConverter,
            $this->randomGenerator,
            $this->timeConverter,
            $this->timeGenerator,
            $this->unixTimeGenerator,
            $this->builder,
            $this->validator
        );
    }

    /**
     * Factory method to support the original auto-assembly behavior.
     */
    public static function fromDefaults(
        bool $useGuids = false,
        bool $force32Bit = false,
        bool $forceNoBigNumber = false,
        bool $ignoreSystemNode = false,
        bool $enablePecl = false
    ): self {
        $resolver = new StrategyResolver();
        $blueprint = $resolver->resolve(
            $useGuids,
            $force32Bit,
            $forceNoBigNumber,
            $ignoreSystemNode,
            $enablePecl
        );

        $builder = new self();
        $builder->withCalculator($blueprint['calculator'])
            ->withCodec($blueprint['codec'])
            ->withDceSecurityGenerator($blueprint['dceSecurityGenerator'])
            ->withNameGenerator($blueprint['nameGenerator'])
            ->withNodeProvider($blueprint['nodeProvider'])
            ->withNumberConverter($blueprint['numberConverter'])
            ->withRandomGenerator($blueprint['randomGenerator'])
            ->withTimeConverter($blueprint['timeConverter'])
            ->withTimeGenerator($blueprint['timeGenerator'])
            ->withUnixTimeGenerator($blueprint['unixTimeGenerator'])
            ->withBuilder($blueprint['builder'])
            ->withValidator($blueprint['validator']);

        return $builder;
    }
}
