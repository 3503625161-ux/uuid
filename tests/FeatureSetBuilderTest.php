<?php

declare(strict_types=1);

namespace Ramsey\Uuid\Test;

use Mockery;
use Ramsey\Uuid\Builder\FallbackBuilder;
use Ramsey\Uuid\FeatureSetBuilder;
use Ramsey\Uuid\Generator\DefaultNameGenerator;
use Ramsey\Uuid\Generator\TimeGeneratorInterface;
use Ramsey\Uuid\Guid\GuidBuilder;
use Ramsey\Uuid\Math\BrickMathCalculator;
use Ramsey\Uuid\Provider\NodeProviderInterface;
use Ramsey\Uuid\Provider\Time\FixedTimeProvider;
use Ramsey\Uuid\Type\Time;
use Ramsey\Uuid\Validator\ValidatorInterface;

class FeatureSetBuilderTest extends TestCase
{
    public function testFromDefaultsBuildsGuidFeatureSet(): void
    {
        $featureSet = FeatureSetBuilder::fromDefaults(true, true)->build();

        $this->assertInstanceOf(GuidBuilder::class, $featureSet->getBuilder());
        $this->assertInstanceOf(BrickMathCalculator::class, $featureSet->getCalculator());
        $this->assertInstanceOf(DefaultNameGenerator::class, $featureSet->getNameGenerator());
    }

    public function testFromDefaultsBuildsFallbackBuilderWhenGuidsDisabled(): void
    {
        $featureSet = FeatureSetBuilder::fromDefaults(false, true)->build();

        $this->assertInstanceOf(FallbackBuilder::class, $featureSet->getBuilder());
    }

    public function testBuilderAllowsOverridingDependenciesChain(): void
    {
        $nodeProvider = Mockery::mock(NodeProviderInterface::class);
        $validator = Mockery::mock(ValidatorInterface::class);
        $timeProvider = new FixedTimeProvider(new Time(1348845514, 277885));

        $featureSet = FeatureSetBuilder::fromDefaults()
            ->withNodeProvider($nodeProvider)
            ->withTimeProvider($timeProvider)
            ->withValidator($validator)
            ->build();

        $this->assertSame($nodeProvider, $featureSet->getNodeProvider());
        $this->assertSame($validator, $featureSet->getValidator());
        $this->assertInstanceOf(TimeGeneratorInterface::class, $featureSet->getTimeGenerator());
    }
}
