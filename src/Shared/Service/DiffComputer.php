<?php

declare(strict_types=1);

namespace App\Shared\Service;

final class DiffComputer
{
    /**
     * @return array<string, array{old: mixed, new: mixed}>
     */
    public function compute(object $before, object $after): array
    {
        if ($before::class !== $after::class) {
            throw new \InvalidArgumentException('Les deux objets doivent être de la même classe.');
        }

        $ref = new \ReflectionClass($before);
        $diff = [];
        foreach ($ref->getProperties() as $property) {
            if ($property->isStatic()) {
                continue;
            }
            $old = $property->getValue($before);
            $new = $property->getValue($after);
            if ($old !== $new) {
                $diff[$property->getName()] = ['old' => $old, 'new' => $new];
            }
        }

        return $diff;
    }
}
