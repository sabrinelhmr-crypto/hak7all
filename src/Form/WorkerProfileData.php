<?php

namespace App\Form;

use App\Entity\City;
use App\Entity\Trade;
use App\Util\Phone;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class WorkerProfileData
{
    #[Assert\NotBlank(message: 'Indiquez votre nom complet.')]
    #[Assert\Length(min: 2, max: 80, minMessage: 'Le nom doit contenir au moins {{ limit }} caractères.', maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères.')]
    public string $fullName = '';

    #[Assert\NotBlank(message: 'Indiquez votre numéro de téléphone.')]
    public string $phone = '';

    #[Assert\NotNull(message: 'Choisissez votre ville.')]
    public ?City $city = null;

    #[Assert\NotNull(message: 'Choisissez votre métier.')]
    public ?Trade $trade = null;

    #[Assert\Positive(message: 'Le tarif doit être un nombre positif.')]
    public ?int $rateTnd = null;

    public bool $isAvailable = false;

    public bool $phonePublic = false;

    #[Assert\Callback]
    public function validate(ExecutionContextInterface $context): void
    {
        if (!Phone::isValid($this->phone)) {
            $context->buildViolation('Numéro tunisien invalide (8 chiffres, ex. 98 123 456).')
                ->atPath('phone')
                ->addViolation();
        }
    }
}
