<?php

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
use Ramsey\Uuid\Provider\Time\SystemTimeProvider;
use Ramsey\Uuid\Provider\TimeProviderInterface;
use Ramsey\Uuid\Validator\ValidatorInterface;

class FeatureSetBuilder
{
    private ?CalculatorInterface $calculator = null;
    private ?NumberConverterInterface $numberConverter = null;
    private ?TimeConverterInterface $timeConverter = null;
    private ?RandomGeneratorInterface $randomGenerator = null;
    private ?TimeGeneratorInterface $timeGenerator = null;
    private ?TimeGeneratorInterface $unixTimeGenerator = null;
    private ?NameGeneratorInterface $nameGenerator = null;
    private ?NodeProviderInterface $nodeProvider = null;
    private ?UuidBuilderInterface $builder = null;
    private ?CodecInterface $codec = null;
    private ?DceSecurityGeneratorInterface $dceSecurityGenerator = null;
    private ?TimeProviderInterface $timeProvider = null;
    private ?ValidatorInterface $validator = null;

    public static function fromDefaults(
        bool $useGuids = false,
        bool $force32Bit = false,
        bool $ignoreSystemNode = false,
        bool $enablePecl = false,
    ): FeatureSet {
        $resolver = new StrategyResolver($force32Bit);
        $builder = new self();

        $calculator = $resolver->resolveCalculator();
        $builder->withCalculator($calculator);

        $numberConverter = $resolver->resolveNumberConverter($calculator);
        $builder->withNumberConverter($numberConverter);

        $timeConverter = $resolver->resolveTimeConverter($calculator);
        $builder->withTimeConverter($timeConverter);

        $randomGenerator = $resolver->resolveRandomGenerator($enablePecl);
        $builder->withRandomGenerator($randomGenerator);

        $nodeProvider = $resolver->resolveNodeProvider($ignoreSystemNode);
        $builder->withNodeProvider($nodeProvider);

        $nameGenerator = $resolver->resolveNameGenerator($enablePecl);
        $builder->withNameGenerator($nameGenerator);

        $timeProvider = new SystemTimeProvider();
        $builder->withTimeProvider($timeProvider);

        $timeGenerator = $resolver->resolveTimeGenerator($enablePecl, $nodeProvider, $timeConverter, $timeProvider);
        $builder->withTimeGenerator($timeGenerator);

        $unixTimeGenerator = $resolver->resolveUnixTimeGenerator($randomGenerator);
        $builder->withUnixTimeGenerator($unixTimeGenerator);

        $uuidBuilder = $resolver->resolveUuidBuilder($useGuids, $numberConverter, $timeConverter);
        $builder->withBuilder($uuidBuilder);

        $codec = $resolver->resolveCodec($useGuids, $uuidBuilder);
        $builder->withCodec($codec);

        $dceSecurityProvider = new Provider\Dce\SystemDceSecurityProvider();
        $dceSecurityGenerator = $resolver->resolveDceSecurityGenerator($numberConverter, $timeGenerator, $dceSecurityProvider);
        $builder->withDceSecurityGenerator($dceSecurityGenerator);

        $validator = $resolver->resolveValidator();
        $builder->withValidator($validator);

        return $builder->build();
    }

    public function withCalculator(CalculatorInterface $calculator): self
    {
        $clone = clone $this;
        $clone->calculator = $calculator;
        return $clone;
    }

    public function withNumberConverter(NumberConverterInterface $numberConverter): self
    {
        $clone = clone $this;
        $clone->numberConverter = $numberConverter;
        return $clone;
    }

    public function withTimeConverter(TimeConverterInterface $timeConverter): self
    {
        $clone = clone $this;
        $clone->timeConverter = $timeConverter;
        return $clone;
    }

    public function withRandomGenerator(RandomGeneratorInterface $randomGenerator): self
    {
        $clone = clone $this;
        $clone->randomGenerator = $randomGenerator;
        return $clone;
    }

    public function withTimeGenerator(TimeGeneratorInterface $timeGenerator): self
    {
        $clone = clone $this;
        $clone->timeGenerator = $timeGenerator;
        return $clone;
    }

    public function withUnixTimeGenerator(TimeGeneratorInterface $unixTimeGenerator): self
    {
        $clone = clone $this;
        $clone->unixTimeGenerator = $unixTimeGenerator;
        return $clone;
    }

    public function withNameGenerator(NameGeneratorInterface $nameGenerator): self
    {
        $clone = clone $this;
        $clone->nameGenerator = $nameGenerator;
        return $clone;
    }

    public function withNodeProvider(NodeProviderInterface $nodeProvider): self
    {
        $clone = clone $this;
        $clone->nodeProvider = $nodeProvider;
        return $clone;
    }

    public function withBuilder(UuidBuilderInterface $builder): self
    {
        $clone = clone $this;
        $clone->builder = $builder;
        return $clone;
    }

    public function withCodec(CodecInterface $codec): self
    {
        $clone = clone $this;
        $clone->codec = $codec;
        return $clone;
    }

    public function withDceSecurityGenerator(DceSecurityGeneratorInterface $dceSecurityGenerator): self
    {
        $clone = clone $this;
        $clone->dceSecurityGenerator = $dceSecurityGenerator;
        return $clone;
    }

    public function withTimeProvider(TimeProviderInterface $timeProvider): self
    {
        $clone = clone $this;
        $clone->timeProvider = $timeProvider;
        return $clone;
    }

    public function withValidator(ValidatorInterface $validator): self
    {
        $clone = clone $this;
        $clone->validator = $validator;
        return $clone;
    }

    public function build(): FeatureSet
    {
        assert(
            $this->calculator !== null
            && $this->numberConverter !== null
            && $this->timeConverter !== null
            && $this->randomGenerator !== null
            && $this->timeGenerator !== null
            && $this->unixTimeGenerator !== null
            && $this->nameGenerator !== null
            && $this->nodeProvider !== null
            && $this->builder !== null
            && $this->codec !== null
            && $this->dceSecurityGenerator !== null
            && $this->validator !== null,
            'All required components must be provided before building a FeatureSet',
        );

        return FeatureSet::fromComponents(
            $this->calculator,
            $this->numberConverter,
            $this->timeConverter,
            $this->randomGenerator,
            $this->timeGenerator,
            $this->unixTimeGenerator,
            $this->nameGenerator,
            $this->nodeProvider,
            $this->builder,
            $this->codec,
            $this->dceSecurityGenerator,
            $this->timeProvider,
            $this->validator,
        );
    }
}