<?php

declare(strict_types=1);

namespace App\Module\Moderation\EventSubscriber;

use App\Module\Moderation\Entity\ChangeProposal;
use App\Module\Moderation\Service\ChangeProposalDiffApplier;
use App\Module\User\Entity\User;
use App\Shared\Service\RevisionLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Workflow\Event\CompletedEvent;

/**
 * Après transition approve sur ChangeProposal : applique le diff, révisions, notification.
 */
final class ApplyChangeOnApproveSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly ChangeProposalDiffApplier $diffApplier,
        private readonly RevisionLogger $revisionLogger,
        private readonly EntityManagerInterface $entityManager,
        private readonly Security $security,
        private readonly MailerInterface $mailer,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.change_proposal.completed.approve' => 'onApproveCompleted',
        ];
    }

    public function onApproveCompleted(CompletedEvent $event): void
    {
        $proposal = $event->getSubject();
        if (!$proposal instanceof ChangeProposal) {
            return;
        }

        $moderator = $this->security->getUser();
        if (!$moderator instanceof User) {
            return;
        }

        $proposal->setModeratedBy($moderator);
        $proposal->setModeratedAt(new \DateTimeImmutable());
        $proposal->setRejectionReason(null);

        $submitter = $proposal->getSubmittedBy();
        if (!$submitter instanceof User) {
            return;
        }

        $diff = $proposal->getDiff();
        $this->diffApplier->apply($proposal, $diff);

        $this->revisionLogger->log(
            $proposal->getEntityType(),
            $proposal->getEntityId(),
            $diff,
            $submitter,
            $moderator,
            false,
        );

        $this->entityManager->flush();

        $email = (new Email())
            ->from(new Address('noreply@publicgraph.local', 'PublicGraph'))
            ->to($submitter->getEmail())
            ->subject('Votre proposition a été acceptée')
            ->text(sprintf(
                'Votre proposition #%d concernant %s (id %d) a été acceptée par la modération.',
                $proposal->getId() ?? 0,
                $proposal->getEntityType(),
                $proposal->getEntityId(),
            ));

        $this->mailer->send($email);
    }
}
