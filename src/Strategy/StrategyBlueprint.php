<?php

declare(strict_types=1);

namespace Ramsey\Uuid\Strategy;

final class StrategyBlueprint
{
    public function __construct(
        public readonly bool $useGuids,
        public readonly bool $force32Bit,
        public readonly bool $ignoreSystemNode,
        public readonly bool $enablePecl,
        public readonly bool $is64Bit,
        public readonly bool $hasBcmath,
        public readonly bool $hasGmp,
        public readonly bool $hasPeclUuid,
    ) {
    }
}