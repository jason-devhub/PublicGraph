<?php

declare(strict_types=1);

namespace App\Shared\Validator;

use App\Module\Influence\Entity\Membership;
use App\Module\Influence\Entity\Position;
use App\Module\Legislation\Entity\LegislativeAction;
use App\Module\Legislation\Entity\RevolvingDoor;
use App\Module\Source\Entity\EntitySource;
use App\Module\Source\Repository\EntitySourceRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class MinSourcesValidator extends ConstraintValidator
{
    public function __construct(
        private readonly EntitySourceRepository $entitySourceRepository,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof MinSources) {
            throw new UnexpectedTypeException($constraint, MinSources::class);
        }

        if (!\is_object($value)) {
            return;
        }

        $id = match (true) {
            $value instanceof Membership,
            $value instanceof Position,
            $value instanceof LegislativeAction,
            $value instanceof RevolvingDoor => $value->getId(),
            default => null,
        };

        if (null === $id) {
            return;
        }

        $entityType = match (true) {
            $value instanceof Membership => EntitySource::ENTITY_MEMBERSHIP,
            $value instanceof Position => EntitySource::ENTITY_POSITION,
            $value instanceof LegislativeAction => EntitySource::ENTITY_LEGISLATIVE_ACTION,
            $value instanceof RevolvingDoor => EntitySource::ENTITY_REVOLVING_DOOR,
            default => null,
        };

        if (null === $entityType) {
            return;
        }

        $n = $this->entitySourceRepository->countFor($entityType, $id);
        if ($n < $constraint->count) {
            $this->context->buildViolation('Au moins {{ count }} source(s) documentaire(s) est (sont) requise(s).')
                ->setParameter('{{ count }}', (string) $constraint->count)
                ->addViolation();
        }
    }
}
