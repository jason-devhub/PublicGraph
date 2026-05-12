<?php

declare(strict_types=1);

namespace App\Module\Moderation\EventSubscriber;

use App\Module\Moderation\Entity\ChangeProposal;
use App\Module\User\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\GuardEvent;

final class ChangeProposalWorkflowGuardSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly Security $security,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.change_proposal.guard.approve' => 'onApprove',
            'workflow.change_proposal.guard.reject' => 'onReject',
            'workflow.change_proposal.guard.withdraw' => 'onWithdraw',
        ];
    }

    public function onApprove(GuardEvent $event): void
    {
        if (!$this->security->isGranted('ROLE_MODERATOR')) {
            $event->setBlocked(true, 'Rôle modérateur requis.');
        }
    }

    public function onReject(GuardEvent $event): void
    {
        if (!$this->security->isGranted('ROLE_MODERATOR')) {
            $event->setBlocked(true, 'Rôle modérateur requis.');

            return;
        }

        $subject = $event->getSubject();
        if (!$subject instanceof ChangeProposal) {
            return;
        }

        $reason = $subject->getRejectionReason();
        $reason = null !== $reason ? trim($reason) : '';
        if (\strlen($reason) < 10) {
            $event->setBlocked(true, 'Le motif de rejet doit contenir au moins 10 caractères.');
        }
    }

    public function onWithdraw(GuardEvent $event): void
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            $event->setBlocked(true, 'Connexion requise.');

            return;
        }

        $subject = $event->getSubject();
        if (!$subject instanceof ChangeProposal) {
            return;
        }

        $author = $subject->getSubmittedBy();
        if (!$author instanceof User || $author->getId() !== $user->getId()) {
            $event->setBlocked(true, 'Seul l\'auteur peut retirer sa proposition.');
        }
    }
}
