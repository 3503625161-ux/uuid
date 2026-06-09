<?php

declare(strict_types=1);

namespace Ramsey\Uuid\Test;

use Mockery;
use Ramsey\Uuid\Builder\UuidBuilderInterface;
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
use Ramsey\Uuid\Validator\ValidatorInterface;

class FeatureSetTest extends TestCase
{
    public function testFeatureSetReturnsProvidedComponents(): void
    {
        $builder = Mockery::mock(UuidBuilderInterface::class);
        $calculator = Mockery::mock(CalculatorInterface::class);
        $codec = Mockery::mock(CodecInterface::class);
        $dceSecurityGenerator = Mockery::mock(DceSecurityGeneratorInterface::class);
        $nameGenerator = Mockery::mock(NameGeneratorInterface::class);
        $nodeProvider = Mockery::mock(NodeProviderInterface::class);
        $numberConverter = Mockery::mock(NumberConverterInterface::class);
        $randomGenerator = Mockery::mock(RandomGeneratorInterface::class);
        $timeConverter = Mockery::mock(TimeConverterInterface::class);
        $timeGenerator = Mockery::mock(TimeGeneratorInterface::class);
        $unixTimeGenerator = Mockery::mock(TimeGeneratorInterface::class);
        $validator = Mockery::mock(ValidatorInterface::class);

        $featureSet = new FeatureSet(
            $builder,
            $calculator,
            $codec,
            $dceSecurityGenerator,
            $nameGenerator,
            $nodeProvider,
            $numberConverter,
            $randomGenerator,
            $timeConverter,
            $timeGenerator,
            $unixTimeGenerator,
            $validator,
        );

        $this->assertSame($builder, $featureSet->getBuilder());
        $this->assertSame($calculator, $featureSet->getCalculator());
        $this->assertSame($codec, $featureSet->getCodec());
        $this->assertSame($dceSecurityGenerator, $featureSet->getDceSecurityGenerator());
        $this->assertSame($nameGenerator, $featureSet->getNameGenerator());
        $this->assertSame($nodeProvider, $featureSet->getNodeProvider());
        $this->assertSame($numberConverter, $featureSet->getNumberConverter());
        $this->assertSame($randomGenerator, $featureSet->getRandomGenerator());
        $this->assertSame($timeConverter, $featureSet->getTimeConverter());
        $this->assertSame($timeGenerator, $featureSet->getTimeGenerator());
        $this->assertSame($unixTimeGenerator, $featureSet->getUnixTimeGenerator());
        $this->assertSame($validator, $featureSet->getValidator());
    }
}
