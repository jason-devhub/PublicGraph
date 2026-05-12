<?php

declare(strict_types=1);

namespace App\Message;

final class ProximityRecalculateForPersonMessage
{
    public function __construct(
        public readonly string $personSlug,
    ) {
    }
}
