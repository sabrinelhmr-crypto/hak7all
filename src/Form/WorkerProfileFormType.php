<?php

namespace App\Form;

use App\Entity\City;
use App\Entity\Trade;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WorkerProfileFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('fullName', TextType::class, [
                'label' => 'Nom complet',
                'attr' => ['autocomplete' => 'name'],
            ])
            ->add('trade', EntityType::class, [
                'label' => 'Métier',
                'class' => Trade::class,
                'choice_label' => 'name',
                'placeholder' => 'Choisir un métier',
                'query_builder' => static fn ($repo) => $repo->createQueryBuilder('t')->orderBy('t.name', 'ASC'),
            ])
            ->add('city', EntityType::class, [
                'label' => 'Ville',
                'class' => City::class,
                'choice_label' => 'name',
                'placeholder' => 'Choisir une ville',
                'query_builder' => static fn ($repo) => $repo->createQueryBuilder('c')->orderBy('c.name', 'ASC'),
            ])
            ->add('phone', TextType::class, [
                'label' => 'Téléphone',
                'attr' => ['autocomplete' => 'tel', 'inputmode' => 'tel'],
                'help' => 'Numéro tunisien à 8 chiffres.',
            ])
            ->add('rateTnd', IntegerType::class, [
                'label' => 'Tarif indicatif (TND / visite)',
                'required' => false,
                'attr' => ['min' => 0, 'placeholder' => 'Ex. 40'],
            ])
            ->add('isAvailable', CheckboxType::class, [
                'label' => 'Je suis disponible maintenant',
                'required' => false,
                'help' => 'Désactivé, vous n’apparaissez pas dans le filtre « Disponibles maintenant » et ne recevez pas de demande urgente.',
            ])
            ->add('phonePublic', CheckboxType::class, [
                'label' => 'Mon numéro est visible par les clients',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => WorkerProfileData::class,
            'csrf_token_id' => 'worker_profile',
        ]);
    }
}
