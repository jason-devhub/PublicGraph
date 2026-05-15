<?php

declare(strict_types=1);

namespace App\Module\Wikidata\Command;

use App\Module\Person\Entity\Person;
use App\Module\User\Entity\User;
use App\Module\Wikidata\Client\WikidataSparqlClient;
use App\Module\Wikidata\Service\WikidataBindingValue;
use App\Module\Wikidata\Service\WikidataMembershipYearConsolidator;
use App\Module\Wikidata\Service\WikidataOrganizationEnsurer;
use App\Module\Wikidata\Service\WikidataPersonImporter;
use App\Module\Wikidata\Service\WikidataPersonMapper;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'app:wikidata:sync-event-participants',
    description: 'Importe les participant·e·s (P1344 et/ou P710 sur l’item édition) aux éditions Wikidata listées dans un TSV et rattache chaque année à une organisation cible (ex. Bilderberg)',
)]
final class WikidataSyncEventParticipantsCommand extends Command
{
    public function __construct(
        private readonly WikidataSparqlClient $sparqlClient,
        private readonly WikidataOrganizationEnsurer $organizationEnsurer,
        private readonly WikidataPersonMapper $personMapper,
        private readonly WikidataPersonImporter $importer,
        private readonly WikidataMembershipYearConsolidator $membershipYearConsolidator,
        private readonly ManagerRegistry $doctrine,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('events-tsv', null, InputOption::VALUE_OPTIONAL, 'Chemin TSV colonnes year, qid, label', $this->projectDir.'/data/bilderberg_conference_years_wikidata.tsv')
            ->addOption('target-organization-qid', null, InputOption::VALUE_OPTIONAL, 'Organisation Wikidata à laquelle rattacher les participations (ex. Q184937 Bilderberg Group)', 'Q184937')
            ->addOption('from-year', null, InputOption::VALUE_OPTIONAL, 'Année minimale incluse')
            ->addOption('to-year', null, InputOption::VALUE_OPTIONAL, 'Année maximale incluse')
            ->addOption('limit-per-year', null, InputOption::VALUE_OPTIONAL, 'Nombre max de personnes par édition', '5000')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Met à jour la fiche personne Wikidata même si elle existe déjà')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Journalise sans persister')
            ->addOption('nationality-iso', null, InputOption::VALUE_OPTIONAL, 'Code ISO-2 (ex. FR) : si le DTO Wikidata n’a pas de nationalités, on applique ce pays à l’import');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $tsvPathRaw = $input->getOption('events-tsv');
        $tsvPath = \is_string($tsvPathRaw) ? $tsvPathRaw : $this->projectDir.'/data/bilderberg_conference_years_wikidata.tsv';
        if (!is_file($tsvPath)) {
            $io->error('Fichier TSV introuvable : '.$tsvPath);

            return Command::FAILURE;
        }

        $orgQidRaw = $input->getOption('target-organization-qid');
        $orgQid = \is_string($orgQidRaw) ? strtoupper(trim($orgQidRaw)) : 'Q184937';
        if (!preg_match('/^Q\d+$/', $orgQid)) {
            $io->error('QID organisation cible invalide : '.$orgQid);

            return Command::FAILURE;
        }

        $fromYear = null;
        $toYear = null;
        $fy = $input->getOption('from-year');
        $ty = $input->getOption('to-year');
        if (null !== $fy && '' !== (string) $fy) {
            $fromYear = max(1, (int) $fy);
        }
        if (null !== $ty && '' !== (string) $ty) {
            $toYear = max(1, (int) $ty);
        }
        if (null !== $fromYear && null !== $toYear && $fromYear > $toYear) {
            $io->error('--from-year ne peut pas dépasser --to-year.');

            return Command::FAILURE;
        }

        $limitPerYear = max(1, min(5000, (int) $input->getOption('limit-per-year')));
        $force = (bool) $input->getOption('force');
        $dryRun = (bool) $input->getOption('dry-run');
        $nationalityIsoRaw = $input->getOption('nationality-iso');
        $syncNationalityIso = null;
        if (\is_string($nationalityIsoRaw) && 2 === \strlen(trim($nationalityIsoRaw)) && ctype_alpha(trim($nationalityIsoRaw))) {
            $syncNationalityIso = strtoupper(trim($nationalityIsoRaw));
        }

        $rows = $this->parseEventsTsv($tsvPath);
        if ([] === $rows) {
            $io->warning('Aucune ligne exploitable dans le TSV.');

            return Command::SUCCESS;
        }

        $admin = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'admin@example.com']);

        $organization = null;
        if (!$dryRun) {
            try {
                $organization = $this->organizationEnsurer->ensure($orgQid, $syncNationalityIso ?? 'FR');
            } catch (\Throwable $e) {
                $io->error('Création / lecture organisation : '.$e->getMessage());

                return Command::FAILURE;
            }
        } else {
            $io->note('Dry-run : pas de persistance.');
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = 0;
        $missingWd = 0;
        $yearsProcessed = 0;

        foreach ($rows as $row) {
            $year = $row['year'];
            $eventQid = $row['qid'];
            if (null !== $fromYear && $year < $fromYear) {
                continue;
            }
            if (null !== $toYear && $year > $toYear) {
                continue;
            }

            ++$yearsProcessed;
            $io->section(\sprintf('Édition %d (%s)', $year, $eventQid));
            $sparql = $this->sparqlClient->buildPersonsParticipantInEventQuery($eventQid, $limitPerYear);
            $bindings = $this->sparqlClient->queryBindings($sparql);
            $io->writeln(\sprintf('Participant·e·s WD : %d (limite %d).', \count($bindings), $limitPerYear));

            $progress = $io->createProgressBar(\count($bindings));
            foreach ($bindings as $binding) {
                $personQid = WikidataBindingValue::optionalString($binding, 'wikidataId');
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
                    $this->importer->ensureParticipationMembershipFromEvent(
                        $dto->wikidataId,
                        $orgQid,
                        $year,
                        $eventQid,
                        $dryRun,
                        $admin,
                    );
                    if (!$dryRun) {
                        $person = $this->doctrine->getRepository(Person::class)->findOneBy(['wikidataId' => strtoupper(trim($dto->wikidataId))]);
                        if (null !== $person) {
                            $this->doctrine->getManager()->refresh($person);
                            $this->membershipYearConsolidator->consolidate($person, $organization, $admin);
                        }
                    }
                } catch (\Throwable $e) {
                    ++$errors;
                    $io->warning($personQid.' : '.$e->getMessage());
                    if (!$dryRun) {
                        $em = $this->doctrine->getManager();
                        if (!$em instanceof EntityManagerInterface) {
                            throw new \LogicException('ORM EntityManager attendu pour la commande sync-event-participants.');
                        }
                        if (!$em->isOpen()) {
                            $this->doctrine->resetManager();
                            $organization = $this->organizationEnsurer->ensure($orgQid, $syncNationalityIso ?? 'FR');
                            $admin = $this->doctrine->getRepository(User::class)->findOneBy(['email' => 'admin@example.com']);
                        }
                    }
                }
                $progress->advance();
            }
            $progress->finish();
            $io->newLine();
        }

        $io->success(\sprintf(
            'Terminé — années traitées: %d, fiches créées: %d, mises à jour: %d, import WD ignoré (sans --force): %d, sans fiche WD: %d, erreurs: %d.',
            $yearsProcessed,
            $created,
            $updated,
            $skipped,
            $missingWd,
            $errors,
        ));

        return Command::SUCCESS;
    }

    /**
     * @return list<array{year: int, qid: string, label: string}>
     */
    private function parseEventsTsv(string $path): array
    {
        $content = file_get_contents($path);
        if (false === $content) {
            return [];
        }
        $lines = preg_split("/\r\n|\n|\r/", trim($content));
        if (false === $lines || [] === $lines) {
            return [];
        }
        $out = [];
        foreach ($lines as $i => $line) {
            if ('' === $line) {
                continue;
            }
            if (0 === $i && str_contains(strtolower($line), 'year')) {
                continue;
            }
            $parts = explode("\t", $line);
            $yearRaw = trim((string) $parts[0]);
            $qidRaw = strtoupper(trim((string) ($parts[1] ?? '')));
            $label = trim((string) ($parts[2] ?? ''));
            if (!ctype_digit($yearRaw)) {
                continue;
            }
            $year = (int) $yearRaw;
            if (!preg_match('/^Q\d+$/', $qidRaw)) {
                continue;
            }
            $out[] = ['year' => $year, 'qid' => $qidRaw, 'label' => $label];
        }

        return $out;
    }
}
