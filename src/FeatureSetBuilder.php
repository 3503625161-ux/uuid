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

class FeatureSetBuilder
{
    private ?CalculatorInterface $calculator = null;
    private ?CodecInterface $codec = null;
    private ?DceSecurityGeneratorInterface $dceSecurityGenerator = null;
    private ?DceSecurityProviderInterface $dceSecurityProvider = null;
    private ?NameGeneratorInterface $nameGenerator = null;
    private ?NodeProviderInterface $nodeProvider = null;
    private ?NumberConverterInterface $numberConverter = null;
    private ?RandomGeneratorInterface $randomGenerator = null;
    private ?TimeConverterInterface $timeConverter = null;
    private ?TimeGeneratorInterface $timeGenerator = null;
    private ?TimeProviderInterface $timeProvider = null;
    private ?TimeGeneratorInterface $unixTimeGenerator = null;
    private ?UuidBuilderInterface $builder = null;
    private ?ValidatorInterface $validator = null;

    /**
     * @var array<string, bool | class-string>
     */
    private array $strategy = [];

    public static function fromDefaults(
        bool $useGuids = false,
        bool $force32Bit = false,
        bool $forceNoBigNumber = false,
        bool $ignoreSystemNode = false,
        bool $enablePecl = false,
    ): self {
        $builder = new self();
        $builder->strategy = (new StrategyResolver(
            $useGuids,
            $force32Bit,
            $forceNoBigNumber,
            $ignoreSystemNode,
            $enablePecl,
        ))->resolve();

        return $builder;
    }

    public function withBuilder(UuidBuilderInterface $builder): self
    {
        $this->builder = $builder;

        return $this;
    }

    public function withCalculator(CalculatorInterface $calculator): self
    {
        $this->calculator = $calculator;

        return $this;
    }

    public function withCodec(CodecInterface $codec): self
    {
        $this->codec = $codec;

        return $this;
    }

    public function withDceSecurityGenerator(DceSecurityGeneratorInterface $dceSecurityGenerator): self
    {
        $this->dceSecurityGenerator = $dceSecurityGenerator;

        return $this;
    }

    public function withDceSecurityProvider(DceSecurityProviderInterface $dceSecurityProvider): self
    {
        $this->dceSecurityProvider = $dceSecurityProvider;

        return $this;
    }

    public function withNameGenerator(NameGeneratorInterface $nameGenerator): self
    {
        $this->nameGenerator = $nameGenerator;

        return $this;
    }

    public function withNodeProvider(NodeProviderInterface $nodeProvider): self
    {
        $this->nodeProvider = $nodeProvider;

        return $this;
    }

    public function withNumberConverter(NumberConverterInterface $numberConverter): self
    {
        $this->numberConverter = $numberConverter;

        return $this;
    }

    public function withRandomGenerator(RandomGeneratorInterface $randomGenerator): self
    {
        $this->randomGenerator = $randomGenerator;

        return $this;
    }

    public function withTimeConverter(TimeConverterInterface $timeConverter): self
    {
        $this->timeConverter = $timeConverter;

        return $this;
    }

    public function withTimeGenerator(TimeGeneratorInterface $timeGenerator): self
    {
        $this->timeGenerator = $timeGenerator;

        return $this;
    }

    public function withTimeProvider(TimeProviderInterface $timeProvider): self
    {
        $this->timeProvider = $timeProvider;

        return $this;
    }

    public function withUnixTimeGenerator(TimeGeneratorInterface $unixTimeGenerator): self
    {
        $this->unixTimeGenerator = $unixTimeGenerator;

        return $this;
    }

    public function withValidator(ValidatorInterface $validator): self
    {
        $this->validator = $validator;

        return $this;
    }

    public function build(): FeatureSet
    {
        $calculator = $this->calculator ?? $this->buildCalculator();
        $numberConverter = $this->numberConverter ?? $this->buildNumberConverter($calculator);
        $timeConverter = $this->timeConverter ?? $this->buildTimeConverter($calculator);
        $nodeProvider = $this->nodeProvider ?? $this->buildNodeProvider();
        $builder = $this->builder ?? $this->buildUuidBuilder($numberConverter, $timeConverter);
        $codec = $this->codec ?? $this->buildCodec($builder);
        $randomGenerator = $this->randomGenerator ?? $this->buildRandomGenerator();
        $nameGenerator = $this->nameGenerator ?? $this->buildNameGenerator();
        $timeProvider = $this->timeProvider ?? new SystemTimeProvider();
        $timeGenerator = $this->timeGenerator ?? $this->buildTimeGenerator($nodeProvider, $timeConverter, $timeProvider);
        $dceSecurityProvider = $this->dceSecurityProvider ?? new SystemDceSecurityProvider();
        $dceSecurityGenerator = $this->dceSecurityGenerator
            ?? new DceSecurityGenerator($numberConverter, $timeGenerator, $dceSecurityProvider);
        $validator = $this->validator ?? new GenericValidator();
        $unixTimeGenerator = $this->unixTimeGenerator ?? new UnixTimeGenerator($randomGenerator);

        return new FeatureSet(
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
    }

    private function buildCalculator(): CalculatorInterface
    {
        return new BrickMathCalculator();
    }

    private function buildNumberConverter(CalculatorInterface $calculator): NumberConverterInterface
    {
        return new GenericNumberConverter($calculator);
    }

    private function buildTimeConverter(CalculatorInterface $calculator): TimeConverterInterface
    {
        if (($this->strategy['timeConverter'] ?? null) === PhpTimeConverter::class) {
            return new PhpTimeConverter($calculator, new GenericTimeConverter($calculator));
        }

        return new GenericTimeConverter($calculator);
    }

    private function buildNodeProvider(): NodeProviderInterface
    {
        if (($this->strategy['nodeProvider'] ?? null) === RandomNodeProvider::class) {
            return new RandomNodeProvider();
        }

        return new FallbackNodeProvider([new SystemNodeProvider(), new RandomNodeProvider()]);
    }

    private function buildUuidBuilder(
        NumberConverterInterface $numberConverter,
        TimeConverterInterface $timeConverter,
    ): UuidBuilderInterface {
        if (($this->strategy['builder'] ?? null) === GuidBuilder::class) {
            return new GuidBuilder($numberConverter, $timeConverter);
        }

        return new FallbackBuilder([
            new Rfc4122UuidBuilder($numberConverter, $timeConverter),
            new NonstandardUuidBuilder($numberConverter, $timeConverter),
        ]);
    }

    private function buildCodec(UuidBuilderInterface $builder): CodecInterface
    {
        if (($this->strategy['codec'] ?? null) === GuidStringCodec::class || $builder instanceof GuidBuilder) {
            return new GuidStringCodec($builder);
        }

        return new StringCodec($builder);
    }

    private function buildRandomGenerator(): RandomGeneratorInterface
    {
        if (($this->strategy['randomGenerator'] ?? null) === PeclUuidRandomGenerator::class) {
            return new PeclUuidRandomGenerator();
        }

        return (new RandomGeneratorFactory())->getGenerator();
    }

    private function buildNameGenerator(): NameGeneratorInterface
    {
        if (($this->strategy['nameGenerator'] ?? null) === PeclUuidNameGenerator::class) {
            return new PeclUuidNameGenerator();
        }

        return (new NameGeneratorFactory())->getGenerator();
    }

    private function buildTimeGenerator(
        NodeProviderInterface $nodeProvider,
        TimeConverterInterface $timeConverter,
        TimeProviderInterface $timeProvider,
    ): TimeGeneratorInterface {
        if (($this->strategy['timeGenerator'] ?? null) === PeclUuidTimeGenerator::class) {
            return new PeclUuidTimeGenerator();
        }

        return (new TimeGeneratorFactory($nodeProvider, $timeConverter, $timeProvider))->getGenerator();
    }
}
