<?php

declare(strict_types=1);

namespace App\Module\Person\Controller;

use App\Module\Catalog\Entity\Country;
use App\Module\Influence\Entity\Membership;
use App\Module\Influence\Entity\Position;
use App\Module\Organization\Entity\Organization;
use App\Module\Organization\Repository\OrganizationRepository;
use App\Module\Person\Entity\Person;
use App\Module\Source\Entity\EntitySource;
use App\Module\Source\Entity\Source;
use App\Module\User\Entity\User;
use App\Module\User\Entity\UserWizardState;
use App\Module\User\Repository\UserWizardStateRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Workflow\Exception\LogicException as WorkflowLogicException;
use Symfony\Component\Workflow\WorkflowInterface;

#[Route('/contribute/person')]
#[IsGranted('create_person')]
final class PersonWizardController extends AbstractController
{
    public function __construct(
        private readonly UserWizardStateRepository $wizardStateRepository,
        private readonly OrganizationRepository $organizationRepository,
        private readonly EntityManagerInterface $entityManager,
        #[Target('person_publication')]
        private readonly WorkflowInterface $personPublicationWorkflow,
    ) {
    }

    #[Route('/new', name: 'app_person_wizard_start', methods: ['GET'])]
    public function start(): Response
    {
        return $this->redirectToRoute('app_person_wizard_step', ['step' => 1]);
    }

    #[Route('/step/{step}', name: 'app_person_wizard_step', requirements: ['step' => '[1-5]'], methods: ['GET', 'POST'])]
    public function step(Request $request, int $step): Response
    {
        $user = $this->requireUser();
        $state = $this->wizardStateRepository->getOrCreate($user, UserWizardState::WIZARD_PERSON_CREATE);
        $json = $state->getStateJson();

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('wizard_step', (string) $request->request->get('_token'))) {
                throw $this->createAccessDeniedException('Jeton CSRF invalide.');
            }

            /** @var array<string, mixed> $all */
            $all = $request->request->all();
            $wizardRoot = $all['wizard'] ?? [];
            $payload = [];
            if (\is_array($wizardRoot)) {
                $key = 'step'.$step;
                if (isset($wizardRoot[$key]) && \is_array($wizardRoot[$key])) {
                    $payload = $wizardRoot[$key];
                }
            }
            $json['step'.$step] = $payload;
            $state->setStateJson($json);
            $this->wizardStateRepository->save($state, true);

            if ($step < 5) {
                return $this->redirectToRoute('app_person_wizard_step', ['step' => $step + 1]);
            }

            return $this->finalize($user, $json);
        }

        return $this->render('person/wizard/step_'.$step.'.html.twig', [
            'step' => $step,
            'data' => $json['step'.$step] ?? [],
            'fullJson' => $json,
            'organizationsSample' => $this->organizationRepository->findBy([], ['officialName' => 'ASC'], 30),
        ]);
    }

    /**
     * @param array<string, mixed> $json
     */
    private function finalize(User $user, array $json): Response
    {
        $s1 = $json['step1'] ?? [];
        $s3 = $json['step3'] ?? [];
        $s4 = $json['step4'] ?? [];

        $orgIdMem = (int) ($s4['organizationId'] ?? 0);
        $memUrl = trim((string) ($s4['sourceUrl'] ?? ''));
        if ($orgIdMem <= 0 || '' === $memUrl) {
            $this->addFlash('danger', 'Une appartenance avec au moins une source URL est obligatoire (étape 4).');

            return $this->redirectToRoute('app_person_wizard_step', ['step' => 4]);
        }

        $givenName = trim((string) ($s1['givenName'] ?? ''));
        $familyName = trim((string) ($s1['familyName'] ?? ''));
        if ('' === $givenName || '' === $familyName) {
            $this->addFlash('danger', 'Nom et prénom obligatoires (étape 1).');

            return $this->redirectToRoute('app_person_wizard_step', ['step' => 1]);
        }

        $person = new Person();
        $person->setGivenName($givenName);
        $person->setFamilyName($familyName);
        $person->setUsageName($this->nullableString($s1['usageName'] ?? null));
        $person->setBirthDate($this->parseDate($s1['birthDate'] ?? null));
        $person->setDeathDate($this->parseDate($s1['deathDate'] ?? null));
        $person->setGender($this->nullableString($s1['gender'] ?? null));
        $person->setRoleCategories(isset($s1['roleCategories']) && \is_array($s1['roleCategories']) ? array_values(array_map(static fn (mixed $x) => (string) $x, $s1['roleCategories'])) : []);
        $person->setCreatedBy($user);

        foreach ($this->splitIsoList((string) ($s1['nationalityIso'] ?? '')) as $iso) {
            $c = $this->entityManager->find(Country::class, $iso);
            if ($c instanceof Country) {
                $person->addNationality($c);
            }
        }

        $this->entityManager->persist($person);
        $this->entityManager->flush();

        $orgIdPos = (int) ($s3['organizationId'] ?? 0);
        $titleFr = trim((string) ($s3['titleFr'] ?? ''));
        if ($orgIdPos > 0 && '' !== $titleFr) {
            $org = $this->organizationRepository->find($orgIdPos);
            if ($org instanceof Organization) {
                $pos = new Position();
                $pos->setPerson($person);
                $pos->setOrganization($org);
                $pos->setTitleFr($titleFr);
                $pos->setNature((string) ($s3['nature'] ?? Position::NATURE_OTHER));
                $pos->setStartDate($this->parseDate((string) ($s3['startDate'] ?? '')) ?? new \DateTimeImmutable('first day of January this year'));
                $pos->setEndDate($this->parseDate($s3['endDate'] ?? null));
                $pos->setCreatedBy($user);
                $this->entityManager->persist($pos);
                $this->entityManager->flush();

                $urlPos = trim((string) ($s3['sourceUrl'] ?? ''));
                if ('' !== $urlPos) {
                    $src = new Source();
                    $src->setUrl($urlPos);
                    $src->setType(Source::TYPE_OTHER);
                    $this->entityManager->persist($src);
                    $this->entityManager->flush();

                    $es = new EntitySource();
                    $es->setSource($src);
                    $es->setEntityType(EntitySource::ENTITY_POSITION);
                    $es->setEntityId((int) $pos->getId());
                    $es->setAddedBy($user);
                    $this->entityManager->persist($es);
                    $this->entityManager->flush();
                }
            }
        }

        $orgIdMem = (int) ($s4['organizationId'] ?? 0);
        if ($orgIdMem > 0) {
            $orgM = $this->organizationRepository->find($orgIdMem);
            if ($orgM instanceof Organization) {
                $mem = new Membership();
                $mem->setPerson($person);
                $mem->setOrganization($orgM);
                $year = $s4['year'] ?? null;
                $mem->setYear(null !== $year && '' !== (string) $year ? (int) $year : null);
                $mem->setStartDate($this->parseDate($s4['startDate'] ?? null));
                $mem->setEndDate($this->parseDate($s4['endDate'] ?? null));
                $mem->setCreatedBy($user);
                $this->entityManager->persist($mem);
                $this->entityManager->flush();

                $urlMem = trim((string) ($s4['sourceUrl'] ?? ''));
                if ('' !== $urlMem) {
                    $srcM = new Source();
                    $srcM->setUrl($urlMem);
                    $srcM->setType(Source::TYPE_OTHER);
                    $this->entityManager->persist($srcM);
                    $this->entityManager->flush();

                    $esM = new EntitySource();
                    $esM->setSource($srcM);
                    $esM->setEntityType(EntitySource::ENTITY_MEMBERSHIP);
                    $esM->setEntityId((int) $mem->getId());
                    $esM->setAddedBy($user);
                    $this->entityManager->persist($esM);
                    $this->entityManager->flush();
                }
            }
        }

        try {
            $this->personPublicationWorkflow->apply($person, 'submit');
            $this->entityManager->flush();
        } catch (WorkflowLogicException $e) {
            $this->addFlash('danger', $e->getMessage());

            return $this->redirectToRoute('app_person_wizard_step', ['step' => 5]);
        }

        $wizardRow = $this->wizardStateRepository->findOneBy([
            'user' => $user,
            'wizardType' => UserWizardState::WIZARD_PERSON_CREATE,
        ]);
        if ($wizardRow instanceof UserWizardState) {
            $this->entityManager->remove($wizardRow);
            $this->entityManager->flush();
        }

        $this->addFlash('success', 'Votre fiche est en attente de modération.');

        return $this->redirectToRoute('app_contributor_dashboard');
    }

    private function requireUser(): User
    {
        $u = $this->getUser();
        if (!$u instanceof User) {
            throw $this->createAccessDeniedException();
        }

        return $u;
    }

    private function nullableString(mixed $v): ?string
    {
        if (null === $v || '' === trim((string) $v)) {
            return null;
        }

        return (string) $v;
    }

    private function parseDate(mixed $v): ?\DateTimeImmutable
    {
        if (null === $v || '' === trim((string) $v)) {
            return null;
        }

        try {
            return new \DateTimeImmutable((string) $v);
        } catch (\Exception) {
            return null;
        }
    }

    /** @return list<string> */
    private function splitIsoList(string $raw): array
    {
        $parts = preg_split('/[\s,;]+/', strtoupper(trim($raw))) ?: [];

        return array_values(array_filter($parts, static fn (string $x): bool => '' !== $x && 2 === \strlen($x)));
    }
}
