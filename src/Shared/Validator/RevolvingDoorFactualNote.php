<?php

declare(strict_types=1);

namespace App\Shared\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD)]
final class RevolvingDoorFactualNote extends Constraint
{
    public string $message = 'Le terme « {{ word }} » est interprétatif. Reformulez en chronologie factuelle : ce qui s\'est passé, à quelles dates, avec quelles sources. Le lecteur jugera.';
}
