<?php

declare(strict_types=1);

namespace Ramsey\Uuid\Test;

use Ramsey\Uuid\Builder\FallbackBuilder;
use Ramsey\Uuid\Codec\StringCodec;
use Ramsey\Uuid\Converter\Time\GenericTimeConverter;
use Ramsey\Uuid\Converter\Time\PhpTimeConverter;
use Ramsey\Uuid\Guid\GuidBuilder;
use Ramsey\Uuid\StrategyResolver;

class StrategyResolverTest extends TestCase
{
    public function testResolverSelectsGuidBlueprint(): void
    {
        $strategy = (new StrategyResolver(true, true))->resolve();

        $this->assertSame(GuidBuilder::class, $strategy['builder']);
        $this->assertSame(PhpTimeConverter::class, $strategy['timeConverter']);
    }

    public function testResolverSelectsFallbackBlueprintFor32BitMode(): void
    {
        $strategy = (new StrategyResolver(false, true))->resolve();

        $this->assertSame(FallbackBuilder::class, $strategy['builder']);
        $this->assertSame(StringCodec::class, $strategy['codec']);
        $this->assertSame(GenericTimeConverter::class, $strategy['timeConverter']);
    }

    public function testResolverExposesEnvironmentCapabilities(): void
    {
        $strategy = (new StrategyResolver())->resolve();

        $this->assertArrayHasKey('supportsBcmath', $strategy);
        $this->assertArrayHasKey('supportsGmp', $strategy);
        $this->assertArrayHasKey('supportsPeclUuid', $strategy);
    }
}
