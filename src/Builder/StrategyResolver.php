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

namespace Ramsey\Uuid\Builder;

use Ramsey\Uuid\Builder\FallbackBuilder;
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
use Ramsey\Uuid\Validator\GenericValidator;
use Ramsey\Uuid\Validator\ValidatorInterface;

use function extension_loaded;
use const PHP_INT_SIZE;

class StrategyResolver
{
    /**
     * @return array<string, mixed>
     */
    public function resolve(
        bool $useGuids = false,
        bool $force32Bit = false,
        bool $forceNoBigNumber = false,
        bool $ignoreSystemNode = false,
        bool $enablePecl = false
    ): array {
        $calculator = new BrickMathCalculator();
        $numberConverter = new GenericNumberConverter($calculator);
        
        $genericTimeConverter = new GenericTimeConverter($calculator);
        $is64Bit = PHP_INT_SIZE === 8 && !$force32Bit;
        $timeConverter = $is64Bit ? new PhpTimeConverter($calculator, $genericTimeConverter) : $genericTimeConverter;

        if ($useGuids) {
            $builder = new GuidBuilder($numberConverter, $timeConverter);
            $codec = new GuidStringCodec($builder);
        } else {
            $builder = new FallbackBuilder([
                new Rfc4122UuidBuilder($numberConverter, $timeConverter),
                new NonstandardUuidBuilder($numberConverter, $timeConverter),
            ]);
            $codec = new StringCodec($builder);
        }

        if ($ignoreSystemNode) {
            $nodeProvider = new RandomNodeProvider();
        } else {
            $nodeProvider = new FallbackNodeProvider([new SystemNodeProvider(), new RandomNodeProvider()]);
        }

        $timeProvider = new SystemTimeProvider();
        
        $hasUuidExt = extension_loaded('uuid');
        $usePecl = $enablePecl || $hasUuidExt;

        if ($usePecl) {
            $randomGenerator = new PeclUuidRandomGenerator();
            $timeGenerator = new PeclUuidTimeGenerator();
            $nameGenerator = new PeclUuidNameGenerator();
        } else {
            $randomGenerator = (new RandomGeneratorFactory())->getGenerator();
            $timeGenerator = (new TimeGeneratorFactory($nodeProvider, $timeConverter, $timeProvider))->getGenerator();
            $nameGenerator = (new NameGeneratorFactory())->getGenerator();
        }

        $dceSecurityProvider = new SystemDceSecurityProvider();
        $dceSecurityGenerator = new DceSecurityGenerator($numberConverter, $timeGenerator, $dceSecurityProvider);

        $unixTimeGenerator = new UnixTimeGenerator($randomGenerator);
        $validator = new GenericValidator();

        return [
            'calculator' => $calculator,
            'codec' => $codec,
            'dceSecurityGenerator' => $dceSecurityGenerator,
            'nameGenerator' => $nameGenerator,
            'nodeProvider' => $nodeProvider,
            'numberConverter' => $numberConverter,
            'randomGenerator' => $randomGenerator,
            'timeConverter' => $timeConverter,
            'timeGenerator' => $timeGenerator,
            'unixTimeGenerator' => $unixTimeGenerator,
            'builder' => $builder,
            'validator' => $validator,
        ];
    }
}
