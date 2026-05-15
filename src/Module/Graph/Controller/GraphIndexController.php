<?php

declare(strict_types=1);

namespace App\Module\Graph\Controller;

use App\Module\Graph\Model\GraphQueryParams;
use App\Module\Person\Entity\Person;
use App\Module\Person\Repository\PersonRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

final class GraphIndexController extends AbstractController
{
    #[Route('/graph', name: 'app_graph_index', methods: ['GET'])]
    public function __invoke(Request $request, PersonRepository $personRepository, TranslatorInterface $translator): Response
    {
        $params = GraphQueryParams::fromRequest($request);
        $focusPerson = null;
        $focus = $request->query->get('focus');
        if (\is_string($focus) && '' !== trim($focus)) {
            $p = $personRepository->findBySlug(trim($focus));
            if ($p instanceof Person && Person::STATUS_APPROVED === $p->getStatus()) {
                $focusPerson = $p;
            }
        }

        $graphRoleLabels = [];
        foreach (['politician', 'civil_servant', 'business_leader', 'media_owner', 'financier', 'lobbyist', 'other_influencer'] as $roleKey) {
            $graphRoleLabels[$roleKey] = $translator->trans('global_graph.role_categories.'.$roleKey);
        }
        $graphOrgTypeLabels = [];
        foreach (['influence_network', 'political_party', 'corporation', 'media_group', 'government_body', 'international_body', 'think_tank', 'lobby_group', 'other'] as $orgKey) {
            $graphOrgTypeLabels[$orgKey] = $translator->trans('global_graph.org_types.'.$orgKey);
        }

        return $this->render('graph/index.html.twig', [
            'graph_params' => $params,
            'focus_person' => $focusPerson,
            'graph_role_labels_json' => json_encode($graphRoleLabels, \JSON_THROW_ON_ERROR | \JSON_HEX_TAG | \JSON_HEX_APOS | \JSON_HEX_AMP | \JSON_HEX_QUOT),
            'graph_org_type_labels_json' => json_encode($graphOrgTypeLabels, \JSON_THROW_ON_ERROR | \JSON_HEX_TAG | \JSON_HEX_APOS | \JSON_HEX_AMP | \JSON_HEX_QUOT),
        ]);
    }
}
