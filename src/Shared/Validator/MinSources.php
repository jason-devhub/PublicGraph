<?php

declare(strict_types=1);

namespace App\Shared\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class MinSources extends Constraint
{
    public function __construct(
        public int $count = 1,
        ?array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct([], $groups, $payload);
    }

    public function validatedBy(): string
    {
        return MinSourcesValidator::class;
    }

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
