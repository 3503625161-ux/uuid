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
use Ramsey\Uuid\Generator\DefaultNameGenerator;
use Ramsey\Uuid\Generator\DefaultTimeGenerator;
use Ramsey\Uuid\Generator\NameGeneratorFactory;
use Ramsey\Uuid\Generator\NameGeneratorInterface;
use Ramsey\Uuid\Generator\PeclUuidNameGenerator;
use Ramsey\Uuid\Generator\PeclUuidRandomGenerator;
use Ramsey\Uuid\Generator\PeclUuidTimeGenerator;
use Ramsey\Uuid\Generator\RandomBytesGenerator;
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

use const PHP_INT_SIZE;

/**
 * StrategyResolver probes the runtime environment and returns the optimal implementation blueprint
 *
 * This class is the single source of truth for "which concrete implementation should be used, given
 * the current PHP build and available extensions". It centralizes all the extension detection (such
 * as ext-bcmath, ext-gmp, ext-uuid), the PHP bit-width introspection, and the boolean policy flags
 * (useGuids, force32Bit, ignoreSystemNode, enablePecl).
 *
 * The resolver returns a {@see StrategyBlueprint} — a plain data object describing each component
 * class name along with the arguments required to instantiate it. The blueprint has no side effects
 * and performs no construction; that responsibility belongs to {@see FeatureSetBuilder}.
 */
class StrategyResolver
{
    private bool $useGuids;
    private bool $force32Bit;
    private bool $ignoreSystemNode;
    private bool $enablePecl;

    public function __construct(
        bool $useGuids = false,
        bool $force32Bit = false,
        bool $ignoreSystemNode = false,
        bool $enablePecl = false,
    ) {
        $this->useGuids = $useGuids;
        $this->force32Bit = $force32Bit;
        $this->ignoreSystemNode = $ignoreSystemNode;
        $this->enablePecl = $enablePecl;
    }

    /**
     * Returns the computed configuration blueprint
     *
     * The returned blueprint is an immutable, pure-data object describing the optimal component
     * selection for the current environment.
     */
    public function resolve(): StrategyBlueprint
    {
        $calculator = $this->buildCalculator();
        $numberConverter = $this->buildNumberConverter($calculator);
        $timeConverter = $this->buildTimeConverter($calculator);
        $builder = $this->buildUuidBuilder($numberConverter, $timeConverter);
        $codec = $this->buildCodec($builder);
        $nodeProvider = $this->buildNodeProvider();
        $randomGenerator = $this->buildRandomGenerator();
        $nameGenerator = $this->buildNameGenerator();
        $timeProvider = new SystemTimeProvider();
        $timeGenerator = $this->buildTimeGenerator($nodeProvider, $timeConverter, $timeProvider);
        $dceSecurityProvider = new SystemDceSecurityProvider();
        $dceSecurityGenerator = new DceSecurityGenerator($numberConverter, $timeGenerator, $dceSecurityProvider);
        $unixTimeGenerator = new UnixTimeGenerator($randomGenerator);
        $validator = new GenericValidator();

        return new StrategyBlueprint(
            builder: $builder,
            calculator: $calculator,
            codec: $codec,
            dceSecurityGenerator: $dceSecurityGenerator,
            nameGenerator: $nameGenerator,
            nodeProvider: $nodeProvider,
            numberConverter: $numberConverter,
            randomGenerator: $randomGenerator,
            timeConverter: $timeConverter,
            timeGenerator: $timeGenerator,
            unixTimeGenerator: $unixTimeGenerator,
            validator: $validator,
        );
    }

    /**
     * Returns true if the ext-uuid PHP extension is available for generating UUIDs
     */
    public function hasPeclUuid(): bool
    {
        return $this->enablePecl;
    }

    /**
     * Returns true when the current PHP build is 64-bit (unless force32Bit was requested)
     */
    public function is64BitSystem(): bool
    {
        return PHP_INT_SIZE === 8 && !$this->force32Bit;
    }

    private function buildCalculator(): CalculatorInterface
    {
        return new BrickMathCalculator();
    }

    private function buildCodec(UuidBuilderInterface $builder): CodecInterface
    {
        if ($this->useGuids) {
            return new GuidStringCodec($builder);
        }

        return new StringCodec($builder);
    }

    private function buildNodeProvider(): NodeProviderInterface
    {
        if ($this->ignoreSystemNode) {
            return new RandomNodeProvider();
        }

        return new FallbackNodeProvider([new SystemNodeProvider(), new RandomNodeProvider()]);
    }

    private function buildNumberConverter(CalculatorInterface $calculator): NumberConverterInterface
    {
        return new GenericNumberConverter($calculator);
    }

    private function buildRandomGenerator(): RandomGeneratorInterface
    {
        if ($this->enablePecl) {
            return new PeclUuidRandomGenerator();
        }

        return (new RandomGeneratorFactory())->getGenerator();
    }

    private function buildTimeGenerator(
        NodeProviderInterface $nodeProvider,
        TimeConverterInterface $timeConverter,
        TimeProviderInterface $timeProvider,
    ): TimeGeneratorInterface {
        if ($this->enablePecl) {
            return new PeclUuidTimeGenerator();
        }

        return (new TimeGeneratorFactory($nodeProvider, $timeConverter, $timeProvider))->getGenerator();
    }

    private function buildNameGenerator(): NameGeneratorInterface
    {
        if ($this->enablePecl) {
            return new PeclUuidNameGenerator();
        }

        return (new NameGeneratorFactory())->getGenerator();
    }

    private function buildTimeConverter(CalculatorInterface $calculator): TimeConverterInterface
    {
        $genericConverter = new GenericTimeConverter($calculator);

        if ($this->is64BitSystem()) {
            return new PhpTimeConverter($calculator, $genericConverter);
        }

        return $genericConverter;
    }

    private function buildUuidBuilder(
        NumberConverterInterface $numberConverter,
        TimeConverterInterface $timeConverter,
    ): UuidBuilderInterface {
        if ($this->useGuids) {
            return new GuidBuilder($numberConverter, $timeConverter);
        }

        return new FallbackBuilder([
            new Rfc4122UuidBuilder($numberConverter, $timeConverter),
            new NonstandardUuidBuilder($numberConverter, $timeConverter),
        ]);
    }
}
