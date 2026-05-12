<?php

declare(strict_types=1);

namespace App\Module\Organization\EventSubscriber;

use App\Module\Organization\Entity\Organization;
use App\Module\Person\Entity\Person;
use App\Module\Search\Service\SearchIndexUpdater;
use App\Module\User\Entity\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Workflow\Event\CompletedEvent;

/**
 * Réindexation Meilisearch et e-mails auteur sur transitions publication.
 */
final class PublicationWorkflowEffectsSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly SearchIndexUpdater $searchIndexUpdater,
        private readonly MailerInterface $mailer,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.person_publication.completed.approve' => 'onPersonApprove',
            'workflow.person_publication.completed.archive' => 'onPersonArchive',
            'workflow.person_publication.completed.reject' => 'onPersonReject',
            'workflow.organization_publication.completed.approve' => 'onOrganizationApprove',
            'workflow.organization_publication.completed.archive' => 'onOrganizationArchive',
            'workflow.organization_publication.completed.reject' => 'onOrganizationReject',
        ];
    }

    public function onPersonApprove(CompletedEvent $event): void
    {
        $subject = $event->getSubject();
        if (!$subject instanceof Person) {
            return;
        }

        $this->searchIndexUpdater->syncPerson($subject);
        $this->notifyAuthor(
            $subject->getCreatedBy(),
            'Fiche personne approuvée',
            'Votre fiche personne a été approuvée et est maintenant publique (ou le sera après indexation).',
        );
    }

    public function onPersonArchive(CompletedEvent $event): void
    {
        $subject = $event->getSubject();
        if (!$subject instanceof Person) {
            return;
        }

        $id = $subject->getId();
        if (null !== $id) {
            $this->searchIndexUpdater->removePerson($id);
        }
    }

    public function onPersonReject(CompletedEvent $event): void
    {
        $subject = $event->getSubject();
        if (!$subject instanceof Person) {
            return;
        }

        $id = $subject->getId();
        if (null !== $id) {
            $this->searchIndexUpdater->removePerson($id);
        }

        $this->notifyAuthor(
            $subject->getCreatedBy(),
            'Fiche personne refusée',
            'Votre fiche personne n\'a pas été acceptée par la modération. Vous pouvez la corriger et la resoumettre.',
        );
    }

    public function onOrganizationApprove(CompletedEvent $event): void
    {
        $subject = $event->getSubject();
        if (!$subject instanceof Organization) {
            return;
        }

        $this->searchIndexUpdater->syncOrganization($subject);
    }

    public function onOrganizationArchive(CompletedEvent $event): void
    {
        $subject = $event->getSubject();
        if (!$subject instanceof Organization) {
            return;
        }

        $id = $subject->getId();
        if (null !== $id) {
            $this->searchIndexUpdater->removeOrganization($id);
        }
    }

    public function onOrganizationReject(CompletedEvent $event): void
    {
        $subject = $event->getSubject();
        if (!$subject instanceof Organization) {
            return;
        }

        $id = $subject->getId();
        if (null !== $id) {
            $this->searchIndexUpdater->removeOrganization($id);
        }
    }

    private function notifyAuthor(?User $author, string $subjectLine, string $body): void
    {
        if (!$author instanceof User) {
            return;
        }

        $email = (new Email())
            ->from(new Address('noreply@publicgraph.local', 'PublicGraph'))
            ->to($author->getEmail())
            ->subject($subjectLine)
            ->text($body);

        $this->mailer->send($email);
    }
}
