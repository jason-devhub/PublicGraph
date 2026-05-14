<?php

declare(strict_types=1);

namespace App\Module\Wikidata\Command;

use App\Module\User\Repository\UserRepository;
use App\Module\Wikidata\Client\WikidataMediaWikiClient;
use App\Module\Wikidata\Client\WikidataSparqlClient;
use App\Module\Wikidata\Service\WikidataBindingValue;
use App\Module\Wikidata\Service\WikidataOrganizationEnsurer;
use App\Module\Wikidata\Service\WikidataPersonImporter;
use App\Module\Wikidata\Service\WikidataPersonMapper;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:wikidata:sync-org-members',
    description: 'Importe les personnes déclarées « membre de » (P463) une organisation Wikidata et crée l’organisation si besoin',
)]
final class WikidataSyncOrganizationMembersCommand extends Command
{
    public function __construct(
        private readonly WikidataSparqlClient $sparqlClient,
        private readonly WikidataMediaWikiClient $mediaWikiClient,
        private readonly WikidataOrganizationEnsurer $organizationEnsurer,
        private readonly WikidataPersonMapper $personMapper,
        private readonly WikidataPersonImporter $importer,
        private readonly UserRepository $userRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('organization-qid', null, InputOption::VALUE_OPTIONAL, 'QID Wikidata de l’organisation (ex. Q3227220 pour Le Siècle)')
            ->addOption('fr-wiki-title', null, InputOption::VALUE_OPTIONAL, 'Titre exact de la page Wikipédia en français (ex. Le_Siècle), résolu via wbgetentities')
            ->addOption('limit', null, InputOption::VALUE_OPTIONAL, 'Nombre max de personnes à traiter', '500')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Met à jour même si la fiche personne existe déjà')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Journalise sans persister')
            ->addOption('nationality-iso', null, InputOption::VALUE_OPTIONAL, 'Code ISO-2 (ex. FR) : si le DTO Wikidata n’a pas de nationalités, on applique ce pays à l’import');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $orgQidRaw = $input->getOption('organization-qid');
        $frTitleRaw = $input->getOption('fr-wiki-title');
        $orgQid = \is_string($orgQidRaw) ? trim($orgQidRaw) : '';
        $frTitle = \is_string($frTitleRaw) ? trim($frTitleRaw) : '';

        if ('' !== $orgQid && '' !== $frTitle) {
            $io->error('Utilise soit --organization-qid soit --fr-wiki-title, pas les deux.');

            return Command::FAILURE;
        }
        if ('' === $orgQid && '' === $frTitle) {
            $io->error('Indique --organization-qid=Q… ou --fr-wiki-title=… (page fr.wikipedia).');

            return Command::FAILURE;
        }

        if ('' !== $frTitle) {
            $resolved = $this->mediaWikiClient->qidFromSiteTitle('frwiki', $frTitle);
            if (null === $resolved) {
                $io->error('Impossible de résoudre le titre Wikipédia vers un QID Wikidata : '.$frTitle);

                return Command::FAILURE;
            }
            $io->note('Titre « '.$frTitle.' » → '.$resolved);
            $orgQid = $resolved;
        }

        if (!preg_match('/^Q\d+$/i', $orgQid)) {
            $io->error('QID organisation invalide : '.$orgQid);

            return Command::FAILURE;
        }
        $orgQid = strtoupper($orgQid);

        $limit = max(1, min(5000, (int) $input->getOption('limit')));
        $force = (bool) $input->getOption('force');
        $dryRun = (bool) $input->getOption('dry-run');
        $nationalityIsoRaw = $input->getOption('nationality-iso');
        $syncNationalityIso = null;
        if (\is_string($nationalityIsoRaw) && 2 === \strlen(trim($nationalityIsoRaw)) && ctype_alpha(trim($nationalityIsoRaw))) {
            $syncNationalityIso = strtoupper(trim($nationalityIsoRaw));
        }

        if (!$dryRun) {
            try {
                $this->organizationEnsurer->ensure($orgQid, $syncNationalityIso ?? 'FR');
            } catch (\Throwable $e) {
                $io->error('Création / lecture organisation : '.$e->getMessage());

                return Command::FAILURE;
            }
        } else {
            $io->note('Dry-run : pas de création d’organisation en base (les personnes ne seraient pas persistées non plus).');
        }

        $sparql = $this->sparqlClient->buildPersonsMemberOfOrganizationQuery($orgQid, $limit);
        $io->note('Requête SPARQL (membres P463)…');
        $bindings = $this->sparqlClient->queryBindings($sparql);
        $io->writeln(\sprintf('Personnes à traiter : %d.', \count($bindings)));

        $admin = $this->userRepository->findOneBy(['email' => 'admin@example.com']);

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = 0;
        $missingWd = 0;
        $progress = $io->createProgressBar(\count($bindings));
        foreach ($bindings as $row) {
            $personQid = WikidataBindingValue::optionalString($row, 'wikidataId');
            if (null === $personQid || '' === $personQid) {
                ++$errors;
                $progress->advance();

                continue;
            }
            $personQid = strtoupper($personQid);
            try {
                $personBinding = $this->sparqlClient->findPersonByQid($personQid);
                if (null === $personBinding) {
                    ++$missingWd;
                    $progress->advance();

                    continue;
                }
                $dto = $this->personMapper->map($personBinding);
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
                $io->warning($personQid.' : '.$e->getMessage());
            }
            $progress->advance();
        }
        $progress->finish();
        $io->newLine(2);
        $io->success(\sprintf(
            'Terminé — créées: %d, mises à jour: %d, ignorées: %d, sans fiche WD: %d, erreurs: %d.',
            $created,
            $updated,
            $skipped,
            $missingWd,
            $errors,
        ));

        return Command::SUCCESS;
    }
}
