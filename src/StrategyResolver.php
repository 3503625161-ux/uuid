<?php

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
use Ramsey\Uuid\Math\CalculatorInterface;
use Ramsey\Uuid\Nonstandard\UuidBuilder as NonstandardUuidBuilder;
use Ramsey\Uuid\Provider\DceSecurityProviderInterface;
use Ramsey\Uuid\Provider\Node\FallbackNodeProvider;
use Ramsey\Uuid\Provider\Node\RandomNodeProvider;
use Ramsey\Uuid\Provider\Node\SystemNodeProvider;
use Ramsey\Uuid\Provider\NodeProviderInterface;
use Ramsey\Uuid\Provider\TimeProviderInterface;
use Ramsey\Uuid\Rfc4122\UuidBuilder as Rfc4122UuidBuilder;
use Ramsey\Uuid\Validator\GenericValidator;
use Ramsey\Uuid\Validator\ValidatorInterface;

use const PHP_INT_SIZE;

class StrategyResolver
{
    private bool $force32Bit;

    public function __construct(bool $force32Bit = false)
    {
        $this->force32Bit = $force32Bit;
    }

    public function resolveCalculator(): CalculatorInterface
    {
        return new Math\BrickMathCalculator();
    }

    public function resolveNumberConverter(CalculatorInterface $calculator): NumberConverterInterface
    {
        return new GenericNumberConverter($calculator);
    }

    public function resolveTimeConverter(CalculatorInterface $calculator): TimeConverterInterface
    {
        $genericConverter = new GenericTimeConverter($calculator);

        if ($this->is64BitSystem()) {
            return new PhpTimeConverter($calculator, $genericConverter);
        }

        return $genericConverter;
    }

    public function resolveRandomGenerator(bool $enablePecl): RandomGeneratorInterface
    {
        if ($enablePecl) {
            return new PeclUuidRandomGenerator();
        }

        return (new RandomGeneratorFactory())->getGenerator();
    }

    public function resolveTimeGenerator(
        bool $enablePecl,
        NodeProviderInterface $nodeProvider,
        TimeConverterInterface $timeConverter,
        TimeProviderInterface $timeProvider,
    ): TimeGeneratorInterface {
        if ($enablePecl) {
            return new PeclUuidTimeGenerator();
        }

        return (new TimeGeneratorFactory($nodeProvider, $timeConverter, $timeProvider))->getGenerator();
    }

    public function resolveNameGenerator(bool $enablePecl): NameGeneratorInterface
    {
        if ($enablePecl) {
            return new PeclUuidNameGenerator();
        }

        return (new NameGeneratorFactory())->getGenerator();
    }

    public function resolveNodeProvider(bool $ignoreSystemNode): NodeProviderInterface
    {
        if ($ignoreSystemNode) {
            return new RandomNodeProvider();
        }

        return new FallbackNodeProvider([new SystemNodeProvider(), new RandomNodeProvider()]);
    }

    public function resolveUuidBuilder(
        bool $useGuids,
        NumberConverterInterface $numberConverter,
        TimeConverterInterface $timeConverter,
    ): UuidBuilderInterface {
        if ($useGuids) {
            return new GuidBuilder($numberConverter, $timeConverter);
        }

        return new FallbackBuilder([
            new Rfc4122UuidBuilder($numberConverter, $timeConverter),
            new NonstandardUuidBuilder($numberConverter, $timeConverter),
        ]);
    }

    public function resolveCodec(bool $useGuids, UuidBuilderInterface $builder): CodecInterface
    {
        if ($useGuids) {
            return new GuidStringCodec($builder);
        }

        return new StringCodec($builder);
    }

    public function resolveDceSecurityGenerator(
        NumberConverterInterface $numberConverter,
        TimeGeneratorInterface $timeGenerator,
        DceSecurityProviderInterface $dceSecurityProvider,
    ): DceSecurityGeneratorInterface {
        return new DceSecurityGenerator($numberConverter, $timeGenerator, $dceSecurityProvider);
    }

    public function resolveUnixTimeGenerator(RandomGeneratorInterface $randomGenerator): TimeGeneratorInterface
    {
        return new UnixTimeGenerator($randomGenerator);
    }

    public function resolveValidator(): ValidatorInterface
    {
        return new GenericValidator();
    }

    public function is64BitSystem(): bool
    {
        return PHP_INT_SIZE === 8 && !$this->force32Bit;
    }
}