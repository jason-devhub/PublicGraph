<?php

declare(strict_types=1);

namespace App\Module\Moderation\EventSubscriber;

use App\Module\Moderation\Entity\ChangeProposal;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Workflow\Event\CompletedEvent;

final class NotifyContributorChangeProposalRejectSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly MailerInterface $mailer,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.change_proposal.completed.reject' => 'onRejectCompleted',
        ];
    }

    public function onRejectCompleted(CompletedEvent $event): void
    {
        $proposal = $event->getSubject();
        if (!$proposal instanceof ChangeProposal) {
            return;
        }

        $submitter = $proposal->getSubmittedBy();
        if (null === $submitter) {
            return;
        }

        $reason = $proposal->getRejectionReason() ?? '';

        $email = (new Email())
            ->from(new Address('noreply@publicgraph.local', 'PublicGraph'))
            ->to($submitter->getEmail())
            ->subject('Votre proposition a été refusée')
            ->text(sprintf(
                "Votre proposition #%d concernant %s (id %d) a été refusée.\n\nMotif :\n%s\n",
                $proposal->getId() ?? 0,
                $proposal->getEntityType(),
                $proposal->getEntityId(),
                $reason,
            ));

        $this->mailer->send($email);
    }
}
