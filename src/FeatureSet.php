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
use Ramsey\Uuid\Provider\NodeProviderInterface;
use Ramsey\Uuid\Validator\ValidatorInterface;

final class FeatureSet
{
    public function __construct(
        private CalculatorInterface $calculator,
        private CodecInterface $codec,
        private UuidBuilderInterface $builder,
        private DceSecurityGeneratorInterface $dceSecurityGenerator,
        private NameGeneratorInterface $nameGenerator,
        private NodeProviderInterface $nodeProvider,
        private NumberConverterInterface $numberConverter,
        private RandomGeneratorInterface $randomGenerator,
        private TimeConverterInterface $timeConverter,
        private TimeGeneratorInterface $timeGenerator,
        private TimeGeneratorInterface $unixTimeGenerator,
        private ValidatorInterface $validator,
    ) {
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
}
