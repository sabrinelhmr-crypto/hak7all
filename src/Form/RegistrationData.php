<?php

namespace App\Form;

use App\Entity\City;
use App\Entity\Trade;
use App\Entity\User;
use App\Util\Phone;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class RegistrationData
{
    #[Assert\NotBlank(message: 'Choisissez un type de compte.')]
    #[Assert\Choice(choices: [User::ROLE_CLIENT, User::ROLE_WORKER], message: 'Type de compte invalide.')]
    public string $role = User::ROLE_CLIENT;

    #[Assert\NotBlank(message: 'Indiquez votre nom complet.')]
    #[Assert\Length(min: 2, max: 80, minMessage: 'Le nom doit contenir au moins {{ limit }} caractères.', maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères.')]
    public string $fullName = '';

    #[Assert\NotBlank(message: 'Indiquez votre numéro de téléphone.')]
    public string $phone = '';

    #[Assert\NotNull(message: 'Choisissez votre ville.')]
    public ?City $city = null;

    #[Assert\NotBlank(message: 'Indiquez votre adresse e-mail.')]
    #[Assert\Email(message: "Cette adresse e-mail n'est pas valide.")]
    public string $email = '';

    #[Assert\NotBlank(message: 'Choisissez un mot de passe.')]
    #[Assert\Length(min: 8, minMessage: 'Le mot de passe doit contenir au moins {{ limit }} caractères.')]
    public string $plainPassword = '';

    public ?Trade $trade = null;

    #[Assert\Positive(message: 'Le tarif doit être un nombre positif.')]
    public ?int $rateTnd = null;

    public bool $phonePublic = false;

    #[Assert\IsTrue(message: "Vous devez accepter les conditions d'utilisation.")]
    public bool $acceptTerms = false;

    #[Assert\Callback]
    public function validate(ExecutionContextInterface $context): void
    {
        if (!Phone::isValid($this->phone)) {
            $context->buildViolation('Numéro tunisien invalide (8 chiffres, ex. 98 123 456).')
                ->atPath('phone')
                ->addViolation();
        }

        if (User::ROLE_WORKER === $this->role) {
            if (null === $this->trade) {
                $context->buildViolation('Choisissez votre métier.')
                    ->atPath('trade')
                    ->addViolation();
            }

            if (!$this->phonePublic) {
                $context->buildViolation('Vous devez accepter que votre numéro soit visible par les clients.')
                    ->atPath('phonePublic')
                    ->addViolation();
            }
        }
    }
}
