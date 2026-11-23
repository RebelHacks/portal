<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

// Step 1: collect names and email, then send a signed registration link.
class RegistrationEmailType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'label' => 'First name',
                'attr' => [
                    'autocomplete' => 'given-name',
                    'autofocus' => true,
                    'maxlength' => 128,
                ],
                'constraints' => [
                    new Assert\NotBlank(message: 'Please enter your first name.'),
                    new Assert\Length(max: 128),
                ],
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Last name',
                'attr' => [
                    'autocomplete' => 'family-name',
                    'maxlength' => 128,
                ],
                'constraints' => [
                    new Assert\NotBlank(message: 'Please enter your last name.'),
                    new Assert\Length(max: 128),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'University email',
                'attr' => [
                    'autocomplete' => 'email',
                    'placeholder' => 'student@unlv.nevada.edu',
                    'maxlength' => 180,
                ],
                'constraints' => [
                    new Assert\NotBlank(message: 'Please enter your university email.'),
                    new Assert\Email(message: 'Please enter a valid email address.'),
                    new Assert\Regex(
                        pattern: '/@(?:[a-z0-9-]+\.)+edu$/i',
                        message: 'Please use a university email address ending in .edu.'
                    ),
                    new Assert\Length(max: 180),
                ],
            ]);
    }
}
