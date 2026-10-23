<?php

namespace App\Form;

use App\Entity\City;
use App\Entity\Trade;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('role', ChoiceType::class, [
                'label' => 'Je suis…',
                'choices' => [
                    'Je cherche un artisan' => User::ROLE_CLIENT,
                    'Je suis artisan' => User::ROLE_WORKER,
                ],
                'expanded' => true,
                'multiple' => false,
            ])
            ->add('fullName', TextType::class, [
                'label' => 'Nom complet',
                'attr' => ['autocomplete' => 'name', 'placeholder' => 'Ex. Amine Ben Ali'],
            ])
            ->add('phone', TextType::class, [
                'label' => 'Téléphone',
                'attr' => ['autocomplete' => 'tel', 'placeholder' => '98 123 456', 'inputmode' => 'tel'],
                'help' => 'Numéro tunisien à 8 chiffres. Indicatif +216 accepté.',
            ])
            ->add('city', EntityType::class, [
                'label' => 'Ville',
                'class' => City::class,
                'choice_label' => 'name',
                'placeholder' => 'Choisir une ville',
                'query_builder' => static fn ($repo) => $repo->createQueryBuilder('c')->orderBy('c.name', 'ASC'),
            ])
            ->add('email', EmailType::class, [
                'label' => 'Adresse e-mail',
                'attr' => ['autocomplete' => 'email', 'placeholder' => 'vous@exemple.tn'],
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => true,
                'first_options' => [
                    'label' => 'Mot de passe',
                    'attr' => ['autocomplete' => 'new-password'],
                    'help' => '8 caractères minimum.',
                ],
                'second_options' => [
                    'label' => 'Confirmer le mot de passe',
                    'attr' => ['autocomplete' => 'new-password'],
                ],
                'invalid_message' => 'Les deux mots de passe ne correspondent pas.',
            ])
            ->add('trade', EntityType::class, [
                'label' => 'Métier',
                'class' => Trade::class,
                'choice_label' => 'name',
                'placeholder' => 'Choisir un métier',
                'required' => false,
                'query_builder' => static fn ($repo) => $repo->createQueryBuilder('t')->orderBy('t.name', 'ASC'),
                'row_attr' => ['class' => 'js-worker-only'],
            ])
            ->add('rateTnd', IntegerType::class, [
                'label' => 'Tarif indicatif (TND / visite)',
                'required' => false,
                'attr' => ['min' => 0, 'placeholder' => 'Ex. 40'],
                'help' => 'Optionnel. Laissez vide si vous préférez en discuter.',
                'row_attr' => ['class' => 'js-worker-only'],
            ])
            ->add('phonePublic', CheckboxType::class, [
                'label' => 'J’accepte que mon numéro soit visible par les clients',
                'required' => false,
                'row_attr' => ['class' => 'js-worker-only'],
            ])
            ->add('acceptTerms', CheckboxType::class, [
                'label' => 'J’accepte les conditions d’utilisation et la politique de confidentialité',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RegistrationData::class,
            'csrf_token_id' => 'register',
        ]);
    }
}
