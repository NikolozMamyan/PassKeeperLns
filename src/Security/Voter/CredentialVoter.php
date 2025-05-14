<?php
// src/Security/Voter/CredentialVoter.php
namespace App\Security\Voter;

use App\Entity\Credential;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class CredentialVoter extends Voter
{
    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, ['VIEW', 'EDIT', 'DELETE'])
            && $subject instanceof Credential;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        // Si l'utilisateur n'est pas connecté, refuser l'accès
        if (!$user instanceof User) {
            return false;
        }

        /** @var Credential $credential */
        $credential = $subject;

        // Vérifier si l'utilisateur est le propriétaire de cet identifiant
        return $credential->getUser()->getId() === $user->getId();
    }
}