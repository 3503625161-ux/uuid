<?php

declare(strict_types=1);

namespace Ramsey\Uuid\Strategy;

use const PHP_INT_SIZE;

final class StrategyResolver
{
    public function is64BitSystem(): bool
    {
        return PHP_INT_SIZE === 8;
    }

    public function isBcmathAvailable(): bool
    {
        return extension_loaded('bcmath');
    }

    public function isGmpAvailable(): bool
    {
        return extension_loaded('gmp');
    }

    public function isPeclUuidAvailable(): bool
    {
        return extension_loaded('uuid');
    }

    public function resolve(
        bool $useGuids = false,
        bool $force32Bit = false,
        bool $ignoreSystemNode = false,
        bool $enablePecl = false,
    ): StrategyBlueprint {
        $is64BitActual = $this->is64BitSystem();
        $hasPeclUuid = $this->isPeclUuidAvailable();

        return new StrategyBlueprint(
            useGuids: $useGuids,
            force32Bit: $force32Bit || !$is64BitActual,
            ignoreSystemNode: $ignoreSystemNode,
            enablePecl: $enablePecl && $hasPeclUuid,
            is64Bit: $is64BitActual && !$force32Bit,
            hasBcmath: $this->isBcmathAvailable(),
            hasGmp: $this->isGmpAvailable(),
            hasPeclUuid: $hasPeclUuid,
        );
    }
}