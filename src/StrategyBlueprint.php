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
 * StrategyBlueprint is an immutable value object carrying the resolved component selection
 *
 * A blueprint is produced by {@see StrategyResolver} after probing the runtime environment. It
 * contains the concrete instances of each feature, and has no detection, side effects, or
 * business logic of its own.
 */
final class StrategyBlueprint
{
    public function __construct(
        public readonly UuidBuilderInterface $builder,
        public readonly CalculatorInterface $calculator,
        public readonly CodecInterface $codec,
        public readonly DceSecurityGeneratorInterface $dceSecurityGenerator,
        public readonly NameGeneratorInterface $nameGenerator,
        public readonly NodeProviderInterface $nodeProvider,
        public readonly NumberConverterInterface $numberConverter,
        public readonly RandomGeneratorInterface $randomGenerator,
        public readonly TimeConverterInterface $timeConverter,
        public readonly TimeGeneratorInterface $timeGenerator,
        public readonly TimeGeneratorInterface $unixTimeGenerator,
        public readonly ValidatorInterface $validator,
    ) {
    }
}
