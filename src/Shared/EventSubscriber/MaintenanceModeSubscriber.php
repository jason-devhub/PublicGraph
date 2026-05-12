<?php

declare(strict_types=1);

namespace App\Shared\EventSubscriber;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class MaintenanceModeSubscriber implements EventSubscriberInterface
{
    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
        private readonly AuthorizationCheckerInterface $authorizationChecker,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 5],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if (str_starts_with($request->getPathInfo(), '/_')) {
            return;
        }

        $flag = $this->projectDir.'/.maintenance';
        if (!is_file($flag)) {
            return;
        }

        if ($this->authorizationChecker->isGranted('ROLE_ADMIN')) {
            return;
        }

        $response = new Response(
            content: $this->renderMaintenanceBody(),
            status: Response::HTTP_SERVICE_UNAVAILABLE,
            headers: ['Retry-After' => '3600', 'Content-Type' => 'text/html; charset=UTF-8'],
        );
        $event->setResponse($response);
    }

    private function renderMaintenanceBody(): string
    {
        return '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><title>Maintenance — PublicGraph</title></head>'
            .'<body style="font-family:system-ui,sans-serif;max-width:40rem;margin:4rem auto;padding:0 1rem;">'
            .'<h1>Maintenance en cours</h1><p>Le site est momentanément indisponible. Merci de réessayer plus tard.</p>'
            .'</body></html>';
    }
}
