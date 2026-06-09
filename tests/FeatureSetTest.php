<?php

declare(strict_types=1);

namespace Ramsey\Uuid\Test;

use Mockery;
use Ramsey\Uuid\Builder\FallbackBuilder;
use Ramsey\Uuid\Converter\TimeConverterInterface;
use Ramsey\Uuid\FeatureSet;
use Ramsey\Uuid\FeatureSetBuilder;
use Ramsey\Uuid\Generator\DefaultNameGenerator;
use Ramsey\Uuid\Generator\PeclUuidTimeGenerator;
use Ramsey\Uuid\Generator\UnixTimeGenerator;
use Ramsey\Uuid\Guid\GuidBuilder;
use Ramsey\Uuid\Math\BrickMathCalculator;
use Ramsey\Uuid\Provider\NodeProviderInterface;
use Ramsey\Uuid\Strategy\StrategyResolver;
use Ramsey\Uuid\Validator\GenericValidator;
use Ramsey\Uuid\Validator\ValidatorInterface;

class FeatureSetTest extends TestCase
{
    public function testFeatureSetHoldsComponentsAsPureValueObject(): void
    {
        $validator = new GenericValidator();
        $nodeProvider = Mockery::mock(NodeProviderInterface::class);

        $builder = FeatureSetBuilder::fromDefaults()
            ->withValidator($validator)
            ->withNodeProvider($nodeProvider);

        $featureSet = $builder->build();

        $this->assertSame($validator, $featureSet->getValidator());
        $this->assertSame($nodeProvider, $featureSet->getNodeProvider());
        $this->assertInstanceOf(BrickMathCalculator::class, $featureSet->getCalculator());
    }

    public function testGuidBuilderIsSelected(): void
    {
        $featureSet = FeatureSetBuilder::fromDefaults(useGuids: true, force32Bit: true)->build();

        $this->assertInstanceOf(GuidBuilder::class, $featureSet->getBuilder());
    }

    public function testFallbackBuilderIsSelected(): void
    {
        $featureSet = FeatureSetBuilder::fromDefaults(useGuids: false, force32Bit: true)->build();

        $this->assertInstanceOf(FallbackBuilder::class, $featureSet->getBuilder());
    }

    public function testGetTimeConverter(): void
    {
        $featureSet = FeatureSetBuilder::fromDefaults()->build();

        $this->assertInstanceOf(TimeConverterInterface::class, $featureSet->getTimeConverter());
    }

    public function testDefaultNameGeneratorIsSelected(): void
    {
        $featureSet = FeatureSetBuilder::fromDefaults()->build();

        $this->assertInstanceOf(DefaultNameGenerator::class, $featureSet->getNameGenerator());
    }

    public function testPeclUuidTimeGeneratorIsSelected(): void
    {
        $featureSet = FeatureSetBuilder::fromDefaults(enablePecl: true)->build();

        $this->assertInstanceOf(PeclUuidTimeGenerator::class, $featureSet->getTimeGenerator());
    }

    public function testGetCalculator(): void
    {
        $featureSet = FeatureSetBuilder::fromDefaults()->build();

        $this->assertInstanceOf(BrickMathCalculator::class, $featureSet->getCalculator());
    }

    public function testGetUnixTimeGenerator(): void
    {
        $featureSet = FeatureSetBuilder::fromDefaults()->build();

        $this->assertInstanceOf(UnixTimeGenerator::class, $featureSet->getUnixTimeGenerator());
    }

    public function testWithValidatorOverridesComponent(): void
    {
        $validator = Mockery::mock(ValidatorInterface::class);

        $featureSet = FeatureSetBuilder::fromDefaults()
            ->withValidator($validator)
            ->build();

        $this->assertSame($validator, $featureSet->getValidator());
    }

    public function testWithNodeProviderOverridesComponent(): void
    {
        $nodeProvider = Mockery::mock(NodeProviderInterface::class);

        $featureSet = FeatureSetBuilder::fromDefaults()
            ->withNodeProvider($nodeProvider)
            ->build();

        $this->assertSame($nodeProvider, $featureSet->getNodeProvider());
    }

    public function testStrategyResolverDetectsEnvironment(): void
    {
        $resolver = new StrategyResolver();

        $blueprint = $resolver->resolve();

        $this->assertIsBool($blueprint->is64Bit);
        $this->assertIsBool($blueprint->hasBcmath);
        $this->assertIsBool($blueprint->hasGmp);
        $this->assertIsBool($blueprint->hasPeclUuid);
    }

    public function testStrategyBlueprintWithForce32Bit(): void
    {
        $resolver = new StrategyResolver();

        $blueprint = $resolver->resolve(force32Bit: true);

        $this->assertFalse($blueprint->is64Bit);
        $this->assertTrue($blueprint->force32Bit);
    }

    public function testStrategyBlueprintWithEnablePeclWithoutExtension(): void
    {
        $resolver = new StrategyResolver();

        $blueprint = $resolver->resolve(enablePecl: true);

        if (!$resolver->isPeclUuidAvailable()) {
            $this->assertFalse($blueprint->enablePecl);
        }
    }

    public function testBuilderChainCallsAreExplicit(): void
    {
        $validator = Mockery::mock(ValidatorInterface::class);
        $nodeProvider = Mockery::mock(NodeProviderInterface::class);

        $featureSet = FeatureSetBuilder::fromDefaults()
            ->withValidator($validator)
            ->withNodeProvider($nodeProvider)
            ->build();

        $this->assertSame($validator, $featureSet->getValidator());
        $this->assertSame($nodeProvider, $featureSet->getNodeProvider());
    }
}
