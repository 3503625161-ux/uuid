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
 * FeatureSet acts as a pure value object container holding component instances.
 *
 * It is used by UuidFactory to retrieve the available features and capabilities of the environment.
 */
class FeatureSet
{
    public function __construct(
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
        private UuidBuilderInterface $builder,
        private ValidatorInterface $validator
    ) {
    }

    /**
     * Returns the builder configured for this environment
     */
    public function getBuilder(): UuidBuilderInterface
    {
        return $this->builder;
    }

    /**
     * Returns the calculator configured for this environment
     */
    public function getCalculator(): CalculatorInterface
    {
        return $this->calculator;
    }

    /**
     * Returns the codec configured for this environment
     */
    public function getCodec(): CodecInterface
    {
        return $this->codec;
    }

    /**
     * Returns the DCE Security generator configured for this environment
     */
    public function getDceSecurityGenerator(): DceSecurityGeneratorInterface
    {
        return $this->dceSecurityGenerator;
    }

    /**
     * Returns the name generator configured for this environment
     */
    public function getNameGenerator(): NameGeneratorInterface
    {
        return $this->nameGenerator;
    }

    /**
     * Returns the node provider configured for this environment
     */
    public function getNodeProvider(): NodeProviderInterface
    {
        return $this->nodeProvider;
    }

    /**
     * Returns the number converter configured for this environment
     */
    public function getNumberConverter(): NumberConverterInterface
    {
        return $this->numberConverter;
    }

    /**
     * Returns the random generator configured for this environment
     */
    public function getRandomGenerator(): RandomGeneratorInterface
    {
        return $this->randomGenerator;
    }

    /**
     * Returns the time converter configured for this environment
     */
    public function getTimeConverter(): TimeConverterInterface
    {
        return $this->timeConverter;
    }

    /**
     * Returns the time generator configured for this environment
     */
    public function getTimeGenerator(): TimeGeneratorInterface
    {
        return $this->timeGenerator;
    }

    /**
     * Returns the Unix Epoch time generator configured for this environment
     */
    public function getUnixTimeGenerator(): TimeGeneratorInterface
    {
        return $this->unixTimeGenerator;
    }

    /**
     * Returns the validator configured for this environment
     */
    public function getValidator(): ValidatorInterface
    {
        return $this->validator;
    }
}
