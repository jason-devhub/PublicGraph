<?php

declare(strict_types=1);

namespace App\Module\Wikidata\Command;

use App\Module\Person\Entity\Person;
use App\Module\Person\Repository\PersonRepository;
use App\Module\User\Repository\UserRepository;
use App\Module\Wikidata\Client\WikidataSparqlClient;
use App\Module\Wikidata\Service\WikidataPersonImporter;
use App\Module\Wikidata\Service\WikidataPersonMapper;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:wikidata:sync-incremental', description: 'Resynchronise les fiches déjà liées à Wikidata')]
final class WikidataSyncIncrementalCommand extends Command
{
    public function __construct(
        private readonly PersonRepository $personRepository,
        private readonly WikidataSparqlClient $sparqlClient,
        private readonly WikidataPersonMapper $personMapper,
        private readonly WikidataPersonImporter $importer,
        private readonly UserRepository $userRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('limit', null, InputOption::VALUE_OPTIONAL, 'Nombre max de fiches', '500')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Sans persistance');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $limit = max(1, (int) $input->getOption('limit'));
        $dryRun = (bool) $input->getOption('dry-run');

        $persons = $this->personRepository->findBatchForWikidataResync($limit);
        $admin = $this->userRepository->findOneBy(['email' => 'admin@example.com']);

        $progress = $io->createProgressBar(\count($persons));
        $ok = 0;
        $fail = 0;
        foreach ($persons as $person) {
            if (!$person instanceof Person || null === $person->getWikidataId()) {
                $progress->advance();

                continue;
            }
            try {
                $binding = $this->sparqlClient->findPersonByQid($person->getWikidataId());
                if (null === $binding) {
                    ++$fail;
                    $progress->advance();

                    continue;
                }
                $dto = $this->personMapper->map($binding);
                $this->importer->importFromDto($dto, true, $dryRun, $admin);
                ++$ok;
            } catch (\Throwable) {
                ++$fail;
            }
            $progress->advance();
        }
        $progress->finish();
        $io->newLine(2);
        $io->success(\sprintf('Resync — OK: %d, échecs: %d%s', $ok, $fail, $dryRun ? ' (dry-run)' : ''));

        return Command::SUCCESS;
    }
}
