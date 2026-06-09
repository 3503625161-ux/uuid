<?php

/**
 * This file is part of the ramsey/uuid library
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @copyright Copyright (c) Ben Ramsey <ben@ramsey.dev>
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

class FeatureSet
{
    public function __construct(
        private UuidBuilderInterface $builder,
        private CalculatorInterface $calculator,
        private CodecInterface $codec,
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
