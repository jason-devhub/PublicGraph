<?php

declare(strict_types=1);

namespace App\Module\User\Security;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class EmailVerifiedUserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        // La vérification e-mail est appliquée via EmailVerifiedVoter sur les actions contributives,
        // pas au login : l'utilisateur peut consulter le site avant validation.
    }

    public function checkPostAuth(UserInterface $user, ?TokenInterface $token = null): void
    {
    }
}
