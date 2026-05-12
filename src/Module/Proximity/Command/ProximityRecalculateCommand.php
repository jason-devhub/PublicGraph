<?php

declare(strict_types=1);

namespace App\Module\Proximity\Command;

use App\Module\Person\Entity\Person;
use App\Module\Person\Repository\PersonRepository;
use App\Module\Proximity\Calculator\ProximityCalculator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:proximity:recalculate', description: 'Recalcule les scores de proximité entre personnes')]
final class ProximityRecalculateCommand extends Command
{
    public function __construct(
        private readonly ProximityCalculator $proximityCalculator,
        private readonly PersonRepository $personRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('person', null, InputOption::VALUE_OPTIONAL, 'Slug de la personne cible')
            ->addOption('full', null, InputOption::VALUE_NONE, 'Équivalent recalcul global (truncate implicite)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $slug = $input->getOption('person');
        if (\is_string($slug) && '' !== trim($slug)) {
            $person = $this->personRepository->findBySlug(trim($slug));
            if (!$person instanceof Person) {
                $io->error('Personne introuvable : '.$slug);

                return Command::FAILURE;
            }
            $this->proximityCalculator->calculateForPerson($person);
            $io->success('Recalcul lancé pour : '.$slug);

            return Command::SUCCESS;
        }

        $stats = $this->proximityCalculator->calculateForAll();
        $io->success(\sprintf('Recalcul global terminé — arêtes stockées : %d', $stats['pairs_stored']));

        return Command::SUCCESS;
    }
}
