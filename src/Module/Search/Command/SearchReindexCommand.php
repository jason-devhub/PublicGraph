<?php

declare(strict_types=1);

namespace App\Module\Search\Command;

use App\Module\Organization\Repository\OrganizationRepository;
use App\Module\Person\Repository\PersonRepository;
use App\Module\Search\Client\MeilisearchClient;
use App\Module\Search\Service\SearchDocumentFactory;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:search:reindex',
    description: 'Reconstruit les index Meilisearch persons / organizations.',
)]
final class SearchReindexCommand extends Command
{
    public function __construct(
        private readonly PersonRepository $personRepository,
        private readonly OrganizationRepository $organizationRepository,
        private readonly SearchDocumentFactory $documentFactory,
        private readonly MeilisearchClient $meilisearchClient,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'index',
            null,
            InputOption::VALUE_REQUIRED,
            'Index cible : persons, organizations ou all.',
            'all',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $which = (string) $input->getOption('index');
        if (!\in_array($which, ['persons', 'organizations', 'all'], true)) {
            $io->error('Option --index doit valoir persons, organizations ou all.');

            return Command::INVALID;
        }

        $this->meilisearchClient->ensureIndexes();

        if (\in_array($which, ['persons', 'all'], true)) {
            $this->meilisearchClient->deleteAllPersonDocuments();
            $persons = $this->personRepository->findApprovedForSearchIndex();
            $batch = [];
            $count = 0;
            foreach ($persons as $person) {
                if (!$this->documentFactory->shouldIndexPerson($person)) {
                    continue;
                }
                $batch[] = $this->documentFactory->buildPersonDocument($person);
                ++$count;
                if (\count($batch) >= 300) {
                    $this->meilisearchClient->upsertPersonDocuments($batch);
                    $batch = [];
                }
            }
            $this->meilisearchClient->upsertPersonDocuments($batch);
            $io->success(sprintf('%d document(s) personne(s) envoyés à Meilisearch.', $count));
        }

        if (\in_array($which, ['organizations', 'all'], true)) {
            $this->meilisearchClient->deleteAllOrganizationDocuments();
            $organizations = $this->organizationRepository->findApprovedForSearchIndex();
            $batch = [];
            $count = 0;
            foreach ($organizations as $organization) {
                if (!$this->documentFactory->shouldIndexOrganization($organization)) {
                    continue;
                }
                $batch[] = $this->documentFactory->buildOrganizationDocument($organization);
                ++$count;
                if (\count($batch) >= 300) {
                    $this->meilisearchClient->upsertOrganizationDocuments($batch);
                    $batch = [];
                }
            }
            $this->meilisearchClient->upsertOrganizationDocuments($batch);
            $io->success(sprintf('%d document(s) organisation(s) envoyés à Meilisearch.', $count));
        }

        return Command::SUCCESS;
    }
}
