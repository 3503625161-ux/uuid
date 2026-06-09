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
use Ramsey\Uuid\Codec\GuidStringCodec;
use Ramsey\Uuid\Codec\StringCodec;
use Ramsey\Uuid\Converter\Number\GenericNumberConverter;
use Ramsey\Uuid\Converter\Time\GenericTimeConverter;
use Ramsey\Uuid\Converter\Time\PhpTimeConverter;
use Ramsey\Uuid\Generator\NameGeneratorFactory;
use Ramsey\Uuid\Generator\PeclUuidNameGenerator;
use Ramsey\Uuid\Generator\PeclUuidRandomGenerator;
use Ramsey\Uuid\Generator\PeclUuidTimeGenerator;
use Ramsey\Uuid\Generator\RandomGeneratorFactory;
use Ramsey\Uuid\Generator\TimeGeneratorFactory;
use Ramsey\Uuid\Guid\GuidBuilder;
use Ramsey\Uuid\Math\BrickMathCalculator;
use Ramsey\Uuid\Provider\Node\FallbackNodeProvider;
use Ramsey\Uuid\Provider\Node\RandomNodeProvider;

use function extension_loaded;
use function function_exists;

use const PHP_INT_SIZE;

class StrategyResolver
{
    public function __construct(
        private bool $useGuids = false,
        private bool $force32Bit = false,
        private bool $forceNoBigNumber = false,
        private bool $ignoreSystemNode = false,
        private bool $enablePecl = false,
    ) {
    }

    /**
     * @return array<string, bool | class-string>
     */
    public function resolve(): array
    {
        $usePeclUuid = $this->enablePecl && $this->supportsPeclUuid();

        return [
            'useGuids' => $this->useGuids,
            'force32Bit' => $this->force32Bit,
            'forceNoBigNumber' => $this->forceNoBigNumber,
            'ignoreSystemNode' => $this->ignoreSystemNode,
            'supportsBcmath' => $this->supportsBcmath(),
            'supportsGmp' => $this->supportsGmp(),
            'supportsPeclUuid' => $this->supportsPeclUuid(),
            'calculator' => BrickMathCalculator::class,
            'numberConverter' => GenericNumberConverter::class,
            'timeConverter' => $this->is64BitSystem() ? PhpTimeConverter::class : GenericTimeConverter::class,
            'nodeProvider' => $this->ignoreSystemNode ? RandomNodeProvider::class : FallbackNodeProvider::class,
            'builder' => $this->useGuids ? GuidBuilder::class : FallbackBuilder::class,
            'codec' => $this->useGuids ? GuidStringCodec::class : StringCodec::class,
            'randomGenerator' => $usePeclUuid ? PeclUuidRandomGenerator::class : RandomGeneratorFactory::class,
            'nameGenerator' => $usePeclUuid ? PeclUuidNameGenerator::class : NameGeneratorFactory::class,
            'timeGenerator' => $usePeclUuid ? PeclUuidTimeGenerator::class : TimeGeneratorFactory::class,
        ];
    }

    private function supportsBcmath(): bool
    {
        return extension_loaded('bcmath');
    }

    private function supportsGmp(): bool
    {
        return extension_loaded('gmp');
    }

    private function supportsPeclUuid(): bool
    {
        return extension_loaded('uuid') && function_exists('uuid_create');
    }

    private function is64BitSystem(): bool
    {
        return PHP_INT_SIZE === 8 && !$this->force32Bit;
    }
}
