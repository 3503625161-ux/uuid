<?php

declare(strict_types=1);

namespace Ramsey\Uuid\Test;

use Mockery;
use Ramsey\Uuid\Builder\FallbackBuilder;
use Ramsey\Uuid\Builder\FeatureSetBuilder;
use Ramsey\Uuid\Converter\TimeConverterInterface;
use Ramsey\Uuid\FeatureSet;
use Ramsey\Uuid\Generator\DefaultNameGenerator;
use Ramsey\Uuid\Generator\PeclUuidTimeGenerator;
use Ramsey\Uuid\Generator\UnixTimeGenerator;
use Ramsey\Uuid\Guid\GuidBuilder;
use Ramsey\Uuid\Math\BrickMathCalculator;
use Ramsey\Uuid\Provider\NodeProviderInterface;
use Ramsey\Uuid\Validator\ValidatorInterface;

class FeatureSetTest extends TestCase
{
    public function testGuidBuilderIsSelected(): void
    {
        $featureSet = FeatureSetBuilder::fromDefaults(true, true)->build();

        $this->assertInstanceOf(GuidBuilder::class, $featureSet->getBuilder());
    }

    public function testFallbackBuilderIsSelected(): void
    {
        $featureSet = FeatureSetBuilder::fromDefaults(false, true)->build();

        $this->assertInstanceOf(FallbackBuilder::class, $featureSet->getBuilder());
    }

    public function testBuilderWithValidatorSetsTheProvidedValidator(): void
    {
        $validator = Mockery::mock(ValidatorInterface::class);

        $featureSet = FeatureSetBuilder::fromDefaults()->withValidator($validator)->build();

        $this->assertSame($validator, $featureSet->getValidator());
    }

    public function testGetTimeConverter(): void
    {
        $featureSet = FeatureSetBuilder::fromDefaults()->build();

        /** @phpstan-ignore method.alreadyNarrowedType */
        $this->assertInstanceOf(TimeConverterInterface::class, $featureSet->getTimeConverter());
    }

    public function testDefaultNameGeneratorIsSelected(): void
    {
        $featureSet = FeatureSetBuilder::fromDefaults()->build();

        $this->assertInstanceOf(DefaultNameGenerator::class, $featureSet->getNameGenerator());
    }

    public function testPeclUuidTimeGeneratorIsSelected(): void
    {
        $featureSet = FeatureSetBuilder::fromDefaults(false, false, false, false, true)->build();

        $this->assertInstanceOf(PeclUuidTimeGenerator::class, $featureSet->getTimeGenerator());
    }

    public function testGetCalculator(): void
    {
        $featureSet = FeatureSetBuilder::fromDefaults()->build();

        $this->assertInstanceOf(BrickMathCalculator::class, $featureSet->getCalculator());
    }

    public function testBuilderWithNodeProvider(): void
    {
        $nodeProvider = Mockery::mock(NodeProviderInterface::class);
        $featureSet = FeatureSetBuilder::fromDefaults()->withNodeProvider($nodeProvider)->build();

        $this->assertSame($nodeProvider, $featureSet->getNodeProvider());
    }

    public function testGetUnixTimeGenerator(): void
    {
        $featureSet = FeatureSetBuilder::fromDefaults()->build();

        $this->assertInstanceOf(UnixTimeGenerator::class, $featureSet->getUnixTimeGenerator());
    }
}
