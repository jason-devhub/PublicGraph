<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\ProximityRecalculateForPersonMessage;
use App\Module\Person\Repository\PersonRepository;
use App\Module\Proximity\Calculator\ProximityCalculator;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class ProximityRecalculateForPersonMessageHandler
{
    public function __construct(
        private readonly PersonRepository $personRepository,
        private readonly ProximityCalculator $proximityCalculator,
    ) {
    }

    public function __invoke(ProximityRecalculateForPersonMessage $message): void
    {
        $person = $this->personRepository->findBySlug($message->personSlug);
        if (null === $person) {
            return;
        }
        $this->proximityCalculator->calculateForPerson($person);
    }
}
