<?php

declare(strict_types=1);

namespace App\Shared\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Nelmio n’expose la fonction Twig `csp_nonce` que lorsque la CSP est activée.
 * `templates/base.html.twig` l’appelle dans une branche « prod », mais Twig compile
 * tout le fichier : sans ce stub, la fonction manque et la compilation Twig échoue.
 *
 * En prod avec CSP Nelmio, le passage compilateur
 * `App\DependencyInjection\Compiler\RemoveCspNonceStubWhenNelmioCspTwigPresentPass`
 * retire ce service pour laisser Nelmio enregistrer la vraie fonction `csp_nonce`.
 */
final class CspNonceStubExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('csp_nonce', $this->cspNonce(...)),
        ];
    }

    public function cspNonce(string $_usage): string
    {
        return '';
    }
}
