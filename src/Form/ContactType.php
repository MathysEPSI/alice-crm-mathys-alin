<?php

namespace App\Form;

use App\Entity\Contact;
use libphonenumber\PhoneNumberFormat;
use Misd\PhoneNumberBundle\Form\Type\PhoneNumberType;
use Misd\PhoneNumberBundle\Validator\Constraints\PhoneNumber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Formulaire de création et de modification d'un contact.
 * Les règles de validation sont portées par l'entité Contact.
 */
class ContactType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstname', TextType::class, [
                'label' => 'Prénom',
                'attr' => ['placeholder' => 'John'],
            ])
            ->add('lastname', TextType::class, [
                'label' => 'Nom',
                'attr' => ['placeholder' => 'Doe'],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr' => ['placeholder' => 'Entrez votre adresse email'],
            ])
            ->add('phone', PhoneNumberType::class, [
                'label' => 'Téléphone',
                'required' => true,
                'default_region' => 'FR',
                'format' => PhoneNumberFormat::INTERNATIONAL,
                'attr' => [
                    'placeholder' => 'Numéro de téléphone',
                    'maxlength' => '17',
                ],
                // Contraintes laissées ici : elles portent sur l'objet PhoneNumber construit par le type de formulaire
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez renseigner un numéro de téléphone.']),
                    new PhoneNumber(['message' => 'Numéro de téléphone invalide.']),
                ],
            ])
            ->add('position', TextType::class, [
                'label' => 'Fonction de l\'interlocuteur',
            ])
            ->add('isMain', CheckboxType::class, [
                'label' => 'Interlocuteur principal',
                'required' => false,
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Enregistrer',
                'attr' => ['class' => 'btn-alice-form'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Contact::class,
        ]);
    }
}
