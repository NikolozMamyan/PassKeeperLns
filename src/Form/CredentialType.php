<?php
// src/Form/CredentialType.php
namespace App\Form;

use App\Entity\Credential;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CredentialType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom du site',
                'attr' => [
                    'placeholder' => 'Ex: Mon compte Gmail',
                    'class' => 'form-control'
                ]
            ])
            ->add('domain', TextType::class, [
                'label' => 'Domaine',
                'attr' => [
                    'placeholder' => 'Ex: gmail.com',
                    'class' => 'form-control'
                ],
                'help' => 'Entrez uniquement le nom de domaine (ex: gmail.com, facebook.com)'
            ])
            ->add('username', TextType::class, [
                'label' => 'Identifiant / Email',
                'attr' => [
                    'placeholder' => 'Ex: votre.email@exemple.com',
                    'class' => 'form-control'
                ]
            ])
            ->add('password', PasswordType::class, [
                'label' => 'Mot de passe',
                'attr' => [
                    'class' => 'form-control',
                    'autocomplete' => 'new-password'
                ],
                'always_empty' => false
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Credential::class,
        ]);
    }
}