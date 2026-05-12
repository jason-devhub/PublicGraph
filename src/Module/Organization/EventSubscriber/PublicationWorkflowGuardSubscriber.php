<?php

declare(strict_types=1);

namespace App\Module\Organization\EventSubscriber;

use App\Module\Person\Entity\Person;
use App\Module\User\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\GuardEvent;

final class PublicationWorkflowGuardSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly Security $security,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.person_publication.guard.submit' => ['onPersonSubmit', 0],
            'workflow.person_publication.guard.approve' => ['onModerator', 0],
            'workflow.person_publication.guard.reject' => ['onModerator', 0],
            'workflow.person_publication.guard.archive' => ['onModerator', 0],
            'workflow.organization_publication.guard.approve' => ['onModerator', 0],
            'workflow.organization_publication.guard.reject' => ['onModerator', 0],
            'workflow.organization_publication.guard.archive' => ['onModerator', 0],
        ];
    }

    public function onPersonSubmit(GuardEvent $event): void
    {
        $subject = $event->getSubject();
        if (!$subject instanceof Person) {
            return;
        }

        $user = $this->security->getUser();
        if ($this->security->isGranted('ROLE_MODERATOR')) {
            return;
        }

        if (!$user instanceof User) {
            $event->setBlocked(true, 'Connexion requise pour soumettre une fiche.');

            return;
        }

        $createdBy = $subject->getCreatedBy();
        if (!$createdBy instanceof User || $createdBy->getId() !== $user->getId()) {
            $event->setBlocked(true, 'Vous n\'êtes pas l\'auteur de ce brouillon.');
        }
    }

    public function onModerator(GuardEvent $event): void
    {
        if (!$this->security->isGranted('ROLE_MODERATOR')) {
            $event->setBlocked(true, 'Rôle modérateur requis.');
        }
    }
}
