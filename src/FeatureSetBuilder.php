<?php

declare(strict_types=1);

namespace Ramsey\Uuid;

use Ramsey\Uuid\Builder\FallbackBuilder;
use Ramsey\Uuid\Builder\UuidBuilderInterface;
use Ramsey\Uuid\Codec\CodecInterface;
use Ramsey\Uuid\Codec\GuidStringCodec;
use Ramsey\Uuid\Codec\StringCodec;
use Ramsey\Uuid\Converter\Number\GenericNumberConverter;
use Ramsey\Uuid\Converter\NumberConverterInterface;
use Ramsey\Uuid\Converter\Time\GenericTimeConverter;
use Ramsey\Uuid\Converter\Time\PhpTimeConverter;
use Ramsey\Uuid\Converter\TimeConverterInterface;
use Ramsey\Uuid\Generator\DceSecurityGenerator;
use Ramsey\Uuid\Generator\DceSecurityGeneratorInterface;
use Ramsey\Uuid\Generator\NameGeneratorFactory;
use Ramsey\Uuid\Generator\NameGeneratorInterface;
use Ramsey\Uuid\Generator\PeclUuidNameGenerator;
use Ramsey\Uuid\Generator\PeclUuidRandomGenerator;
use Ramsey\Uuid\Generator\PeclUuidTimeGenerator;
use Ramsey\Uuid\Generator\RandomGeneratorFactory;
use Ramsey\Uuid\Generator\RandomGeneratorInterface;
use Ramsey\Uuid\Generator\TimeGeneratorFactory;
use Ramsey\Uuid\Generator\TimeGeneratorInterface;
use Ramsey\Uuid\Generator\UnixTimeGenerator;
use Ramsey\Uuid\Guid\GuidBuilder;
use Ramsey\Uuid\Math\BrickMathCalculator;
use Ramsey\Uuid\Math\CalculatorInterface;
use Ramsey\Uuid\Nonstandard\UuidBuilder as NonstandardUuidBuilder;
use Ramsey\Uuid\Provider\Dce\SystemDceSecurityProvider;
use Ramsey\Uuid\Provider\DceSecurityProviderInterface;
use Ramsey\Uuid\Provider\Node\FallbackNodeProvider;
use Ramsey\Uuid\Provider\Node\RandomNodeProvider;
use Ramsey\Uuid\Provider\Node\SystemNodeProvider;
use Ramsey\Uuid\Provider\NodeProviderInterface;
use Ramsey\Uuid\Provider\Time\SystemTimeProvider;
use Ramsey\Uuid\Provider\TimeProviderInterface;
use Ramsey\Uuid\Rfc4122\UuidBuilder as Rfc4122UuidBuilder;
use Ramsey\Uuid\Strategy\StrategyBlueprint;
use Ramsey\Uuid\Strategy\StrategyResolver;
use Ramsey\Uuid\Validator\GenericValidator;
use Ramsey\Uuid\Validator\ValidatorInterface;

use const PHP_INT_SIZE;

final class FeatureSetBuilder
{
    private ?CalculatorInterface $calculator = null;
    private ?CodecInterface $codec = null;
    private ?UuidBuilderInterface $builder = null;
    private ?DceSecurityGeneratorInterface $dceSecurityGenerator = null;
    private ?NameGeneratorInterface $nameGenerator = null;
    private ?NodeProviderInterface $nodeProvider = null;
    private ?NumberConverterInterface $numberConverter = null;
    private ?RandomGeneratorInterface $randomGenerator = null;
    private ?TimeConverterInterface $timeConverter = null;
    private ?TimeGeneratorInterface $timeGenerator = null;
    private ?TimeGeneratorInterface $unixTimeGenerator = null;
    private ?ValidatorInterface $validator = null;

    private ?TimeProviderInterface $timeProvider = null;
    private ?DceSecurityProviderInterface $dceSecurityProvider = null;
    private ?StrategyBlueprint $blueprint = null;

    public static function fromDefaults(
        bool $useGuids = false,
        bool $force32Bit = false,
        bool $ignoreSystemNode = false,
        bool $enablePecl = false,
    ): self {
        $resolver = new StrategyResolver();
        $blueprint = $resolver->resolve($useGuids, $force32Bit, $ignoreSystemNode, $enablePecl);

        $builder = new self();
        $builder->blueprint = $blueprint;

        $calculator = new BrickMathCalculator();
        $numberConverter = new GenericNumberConverter($calculator);

        $genericTimeConverter = new GenericTimeConverter($calculator);
        $timeConverter = $blueprint->is64Bit
            ? new PhpTimeConverter($calculator, $genericTimeConverter)
            : $genericTimeConverter;

        $randomGenerator = $blueprint->enablePecl
            ? new PeclUuidRandomGenerator()
            : (new RandomGeneratorFactory())->getGenerator();

        $nodeProvider = $blueprint->ignoreSystemNode
            ? new RandomNodeProvider()
            : new FallbackNodeProvider([new SystemNodeProvider(), new RandomNodeProvider()]);

        $timeProvider = new SystemTimeProvider();

        $timeGenerator = $blueprint->enablePecl
            ? new PeclUuidTimeGenerator()
            : (new TimeGeneratorFactory($nodeProvider, $timeConverter, $timeProvider))->getGenerator();

        $nameGenerator = $blueprint->enablePecl
            ? new PeclUuidNameGenerator()
            : (new NameGeneratorFactory())->getGenerator();

        $dceSecurityProvider = new SystemDceSecurityProvider();
        $dceSecurityGenerator = new DceSecurityGenerator(
            $numberConverter,
            $timeGenerator,
            $dceSecurityProvider,
        );

        $uuidBuilder = $blueprint->useGuids
            ? new GuidBuilder($numberConverter, $timeConverter)
            : new FallbackBuilder([
                new Rfc4122UuidBuilder($numberConverter, $timeConverter),
                new NonstandardUuidBuilder($numberConverter, $timeConverter),
            ]);

        $codec = $blueprint->useGuids
            ? new GuidStringCodec($uuidBuilder)
            : new StringCodec($uuidBuilder);

        $unixTimeGenerator = new UnixTimeGenerator($randomGenerator);
        $validator = new GenericValidator();

        $builder->calculator = $calculator;
        $builder->codec = $codec;
        $builder->builder = $uuidBuilder;
        $builder->dceSecurityGenerator = $dceSecurityGenerator;
        $builder->nameGenerator = $nameGenerator;
        $builder->nodeProvider = $nodeProvider;
        $builder->numberConverter = $numberConverter;
        $builder->randomGenerator = $randomGenerator;
        $builder->timeConverter = $timeConverter;
        $builder->timeGenerator = $timeGenerator;
        $builder->unixTimeGenerator = $unixTimeGenerator;
        $builder->validator = $validator;
        $builder->timeProvider = $timeProvider;
        $builder->dceSecurityProvider = $dceSecurityProvider;

        return $builder;
    }

    public function withCalculator(CalculatorInterface $calculator): self
    {
        $this->calculator = $calculator;
        $this->numberConverter = new GenericNumberConverter($calculator);

        $genericConverter = new GenericTimeConverter($calculator);
        $is64Bit = $this->blueprint->is64Bit ?? (PHP_INT_SIZE === 8);
        $this->timeConverter = $is64Bit
            ? new PhpTimeConverter($calculator, $genericConverter)
            : $genericConverter;

        if ($this->nodeProvider !== null && $this->timeProvider !== null) {
            $this->timeGenerator = $this->buildTimeGenerator();
        }

        return $this;
    }

    public function withCodec(CodecInterface $codec): self
    {
        $this->codec = $codec;

        return $this;
    }

    public function withBuilder(UuidBuilderInterface $builder): self
    {
        $this->builder = $builder;

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

        if ($this->timeConverter !== null && $this->timeProvider !== null) {
            $this->timeGenerator = $this->buildTimeGenerator();
        }

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

    public function withTimeProvider(TimeProviderInterface $timeProvider): self
    {
        $this->timeProvider = $timeProvider;

        if ($this->nodeProvider !== null && $this->timeConverter !== null) {
            $this->timeGenerator = $this->buildTimeGenerator();
        }

        return $this;
    }

    public function withDceSecurityProvider(DceSecurityProviderInterface $dceSecurityProvider): self
    {
        $this->dceSecurityProvider = $dceSecurityProvider;

        if ($this->numberConverter !== null && $this->timeGenerator !== null) {
            $this->dceSecurityGenerator = new DceSecurityGenerator(
                $this->numberConverter,
                $this->timeGenerator,
                $dceSecurityProvider,
            );
        }

        return $this;
    }

    public function build(): FeatureSet
    {
        return new FeatureSet(
            calculator: $this->calculator ?? throw new \LogicException('Calculator is not set; call fromDefaults() or withCalculator() first'),
            codec: $this->codec ?? throw new \LogicException('Codec is not set; call fromDefaults() or withCodec() first'),
            builder: $this->builder ?? throw new \LogicException('Builder is not set; call fromDefaults() or withBuilder() first'),
            dceSecurityGenerator: $this->dceSecurityGenerator ?? throw new \LogicException('DCE Security generator is not set; call fromDefaults() or withDceSecurityGenerator() first'),
            nameGenerator: $this->nameGenerator ?? throw new \LogicException('Name generator is not set; call fromDefaults() or withNameGenerator() first'),
            nodeProvider: $this->nodeProvider ?? throw new \LogicException('Node provider is not set; call fromDefaults() or withNodeProvider() first'),
            numberConverter: $this->numberConverter ?? throw new \LogicException('Number converter is not set; call fromDefaults() or withNumberConverter() first'),
            randomGenerator: $this->randomGenerator ?? throw new \LogicException('Random generator is not set; call fromDefaults() or withRandomGenerator() first'),
            timeConverter: $this->timeConverter ?? throw new \LogicException('Time converter is not set; call fromDefaults() or withTimeConverter() first'),
            timeGenerator: $this->timeGenerator ?? throw new \LogicException('Time generator is not set; call fromDefaults() or withTimeGenerator() first'),
            unixTimeGenerator: $this->unixTimeGenerator ?? throw new \LogicException('Unix time generator is not set; call fromDefaults() or withUnixTimeGenerator() first'),
            validator: $this->validator ?? throw new \LogicException('Validator is not set; call fromDefaults() or withValidator() first'),
        );
    }

    private function buildTimeGenerator(): TimeGeneratorInterface
    {
        assert($this->nodeProvider !== null);
        assert($this->timeConverter !== null);
        assert($this->timeProvider !== null);

        $enablePecl = $this->blueprint->enablePecl ?? false;

        if ($enablePecl) {
            return new PeclUuidTimeGenerator();
        }

        return (new TimeGeneratorFactory(
            $this->nodeProvider,
            $this->timeConverter,
            $this->timeProvider,
        ))->getGenerator();
    }
}
