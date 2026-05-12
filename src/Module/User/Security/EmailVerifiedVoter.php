<?php

declare(strict_types=1);

namespace App\Module\User\Security;

use App\Module\User\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Refuse certaines actions contributives si l’e-mail n’est pas vérifié.
 */
final class EmailVerifiedVoter extends Voter
{
    public const CREATE_PERSON = 'create_person';

    public const CREATE_PROPOSAL = 'create_proposal';

    public const RESEND_VERIFICATION = 'resend_verification';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return \in_array($attribute, [self::CREATE_PERSON, self::CREATE_PROPOSAL, self::RESEND_VERIFICATION], true);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        return null !== $user->getEmailVerifiedAt();
    }
}
