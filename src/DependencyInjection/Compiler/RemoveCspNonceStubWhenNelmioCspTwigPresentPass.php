<?php

declare(strict_types=1);

namespace App\DependencyInjection\Compiler;

use App\Shared\Twig\CspNonceStubExtension;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Si la CSP Nelmio est active, son extension Twig enregistre déjà la fonction `csp_nonce`.
 * Dans ce cas on retire notre stub pour éviter deux fonctions Twig du même nom.
 *
 * Ce passage doit s’exécuter avant {@see \Symfony\Bundle\TwigBundle\DependencyInjection\Compiler\TwigEnvironmentPass}
 * (priorité 5 : après ExtensionPass=10 vient notre passage, puis TwigEnvironmentPass=0).
 */
final class RemoveCspNonceStubWhenNelmioCspTwigPresentPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(CspNonceStubExtension::class)) {
            return;
        }

        if ($container->hasDefinition('nelmio_security.twig_extension')) {
            $container->removeDefinition(CspNonceStubExtension::class);
        }
    }
}
