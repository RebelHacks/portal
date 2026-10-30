<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class RegistrationFormType extends AbstractType
{
    public function buildForm(
        FormBuilderInterface $builder,
        array $options
    ): void {
        $builder
            ->add('firstName', TextType::class, [
                'constraints'=> [
                    new Assert\NotBlank(),
                    new Assert\Length(max: 128),
                ],
            ])
            ->add('lastName', TextType::class, [
                'constraints'=> [
                    new Assert\NotBlank(),
                    new Assert\Length(max: 128),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'University Email',
                'attr' => [
                    'autocomplete' => 'email',
                    'placeholder' => 'student@unlv.nevada.edu',
                ],
                'constraints'=> [
                    new Assert\NotBlank(
                        message: 'Please enter your university email.'
                    ),
                    new Assert\Email(
                        message: 'Please enter a valid email address.'
                    ),
                    new Assert\Regex(
                        pattern: '/@(?:[a-z0-9-]+\.)+edu$/i',
                        message: 'Please use a university email address ending in .edu.'
                    )
                ],
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'invalid_message' => 'Passwords do not match.',
                'first_options' => [
                    'label' => 'Password',
                    'attr' => ['autocomplete' => 'new-password'],
                ],
                'second_options' => [
                    'label' => 'Confirm Password',
                    'attr' => ['autocomplete' => 'new-password'],
                ],
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(min: 8, max:4096),
                    new Assert\Regex(
                        pattern: '/^(?=.*[a-z])(?=.*[A-Z])(?=.*[^A-Za-z0-9\s])\S+$/',
                        message: 'Password must contain uppercase, lowercase, a number, and a special character, with no spaces.'
                    ),
                ]
            ])
            ->add('major', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(max: 50),
                ]
            ])
            ->add('resume', FileType::class, [
                'label' => 'Resume (PDF)',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new Assert\File(
                        maxSize: '10M',
                        mimeTypes: ['application/pdf'],
                        mimeTypesMessage: 'Please upload a PDF resume.'
                    )
                ]
            ])
            ->add('agreeTerms', CheckboxType::class, [
                'mapped' => false,
                'constraints' => [
                    new Assert\IsTrue(
                        message: 'You must agree to the terms.'
                    ),
                ],
            ]);
    }

    public function configureOptions(
        OptionsResolver $resolver
    ): void {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
