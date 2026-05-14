<?php

declare(strict_types=1);

namespace App\Module\Wikidata\Command;

use App\Module\User\Repository\UserRepository;
use App\Module\Wikidata\Client\WikidataCountryQids;
use App\Module\Wikidata\Client\WikidataSparqlClient;
use App\Module\Wikidata\Service\WikidataPersonImporter;
use App\Module\Wikidata\Service\WikidataPersonMapper;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:wikidata:sync-people', description: 'Synchronise des personnes depuis Wikidata (import initial)')]
final class WikidataSyncPeopleCommand extends Command
{
    public function __construct(
        private readonly WikidataSparqlClient $sparqlClient,
        private readonly WikidataPersonMapper $personMapper,
        private readonly WikidataPersonImporter $importer,
        private readonly UserRepository $userRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('country', null, InputOption::VALUE_REQUIRED, 'Code ISO (FR, DE, …), EU ou G7')
            ->addOption('category', null, InputOption::VALUE_REQUIRED, 'politician, minister, president, civil_servant, business_leader ou all')
            ->addOption('limit', null, InputOption::VALUE_OPTIONAL, 'Nombre max de résultats', '500')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Met à jour même si la fiche existe déjà')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Journalise sans persister');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $countryRaw = $input->getOption('country');
        $categoryRaw = $input->getOption('category');
        if (!\is_string($countryRaw) || '' === trim($countryRaw) || !\is_string($categoryRaw) || '' === trim($categoryRaw)) {
            $io->error('Les options --country et --category sont obligatoires.');

            return Command::FAILURE;
        }
        $country = strtoupper(trim($countryRaw));
        $category = strtolower(trim($categoryRaw));
        $limit = max(1, min(5000, (int) $input->getOption('limit')));
        $force = (bool) $input->getOption('force');
        $dryRun = (bool) $input->getOption('dry-run');

        $syncNationalityIso = null;
        if (!\in_array($country, ['EU', 'G7'], true) && 2 === \strlen($country) && ctype_alpha($country)) {
            $syncNationalityIso = $country;
        }

        $countryQids = match ($country) {
            'G7' => WikidataCountryQids::g7NationalityQids(),
            'EU' => WikidataCountryQids::euNationalityQids(),
            default => WikidataCountryQids::nationalityQidsForIso($country),
        };
        if ([] === $countryQids) {
            $io->error('Pays inconnu ou sans correspondance Wikidata : '.$country);

            return Command::FAILURE;
        }

        try {
            $occQids = match ($category) {
                'politician' => ['Q82955'],
                // Métier « ministre » (Q83307) — beaucoup plus restreint que « politicien » (Q82955).
                'minister' => ['Q83307'],
                // Métier « président » (Q30461) — chef d’État / présidence au sens Wikidata ; pas les mandats seuls (P39).
                'president' => ['Q30461'],
                'civil_servant' => ['Q193391', 'Q486839', 'Q2285706'],
                'business_leader' => ['Q43845', 'Q15978631'],
                'all' => ['Q82955', 'Q83307', 'Q30461', 'Q193391', 'Q486839', 'Q43845', 'Q15978631'],
            };
        } catch (\UnhandledMatchError) {
            $io->error(\sprintf(
                'Catégorie inconnue : "%s". Utilise : politician, minister, president, civil_servant, business_leader, all.',
                $category,
            ));

            return Command::FAILURE;
        }

        $sparql = $this->sparqlClient->buildPersonsByCountryQuery($countryQids, $occQids, $limit);
        $io->note('Requête SPARQL envoyée à Wikidata…');
        $bindings = $this->sparqlClient->queryBindings($sparql);
        $io->writeln(\sprintf('Résultats : %d ligne(s).', \count($bindings)));

        $admin = $this->userRepository->findOneBy(['email' => 'admin@example.com']);

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = 0;
        $progress = $io->createProgressBar(\count($bindings));
        foreach ($bindings as $row) {
            try {
                $dto = $this->personMapper->map($row);
                $r = $this->importer->importFromDto($dto, $force, $dryRun, $admin, $syncNationalityIso);
                if ($r['skipped']) {
                    ++$skipped;
                } elseif ($r['created']) {
                    ++$created;
                } elseif ($r['updated']) {
                    ++$updated;
                }
            } catch (\Throwable $e) {
                ++$errors;
                $io->warning($e->getMessage());
            }
            $progress->advance();
        }
        $progress->finish();
        $io->newLine(2);
        $io->success(\sprintf(
            'Terminé — créés: %d, mis à jour: %d, ignorés: %d, erreurs: %d%s',
            $created,
            $updated,
            $skipped,
            $errors,
            $dryRun ? ' (dry-run)' : '',
        ));

        return Command::SUCCESS;
    }
}
