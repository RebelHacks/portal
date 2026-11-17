<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

// Step 2 of sign-up, reached from the emailed link. The address comes from that link, so it isn't a field here.
class RegistrationFormType extends AbstractType
{
    public function buildForm(
        FormBuilderInterface $builder,
        array $options
    ): void {
        $builder
            ->add('firstName', TextType::class, [
                'attr' => ['autocomplete' => 'given-name'],
                'constraints'=> [
                    new Assert\NotBlank(),
                    new Assert\Length(max: 128),
                ],
            ])
            ->add('lastName', TextType::class, [
                'attr' => ['autocomplete' => 'family-name'],
                'constraints'=> [
                    new Assert\NotBlank(),
                    new Assert\Length(max: 128),
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
                    'label' => 'Confirm password',
                    'attr' => ['autocomplete' => 'new-password'],
                ],
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(min: 8, max:4096),
                    new Assert\Regex(
                        pattern: '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9\s])\S+$/',
                        message: 'Password must contain uppercase, lowercase, a number, and a special character, with no spaces.'
                    ),
                ]
            ])
            // The rest belongs to this year's application, not the account, so it isn't mapped to User.
            ->add('school', TextType::class, [
                'mapped' => false,
                'attr' => ['placeholder' => 'e.g. University of Nevada, Las Vegas', 'autocomplete' => 'organization'],
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(max: 128),
                ]
            ])
            ->add('major', TextType::class, [
                'mapped' => false,
                'attr' => ['placeholder' => 'e.g. Computer Science'],
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(max: 128),
                ]
            ])
            // Yes or No, not a checkbox: an unticked box can't tell "no" from "skipped".
            ->add('attendedBefore', ChoiceType::class, [
                'mapped' => false,
                'label' => 'Have you been to RebelHacks before?',
                'choices' => ['Yes' => true, 'No' => false],
                'expanded' => true,
                'constraints' => [
                    new Assert\NotNull(message: 'Please answer yes or no.'),
                ]
            ])
            ->add('resume', FileType::class, [
                'label' => 'Resume',
                'mapped' => false,
                'required' => true,
                'attr' => ['accept' => 'application/pdf'],
                'constraints' => [
                    new Assert\NotNull(message: 'Please upload your resume.'),
                    new Assert\File(
                        maxSize: '10M',
                        mimeTypes: ['application/pdf'],
                        mimeTypesMessage: 'Please upload a PDF resume.'
                    )
                ]
            ])
            // Only checked at sign-up for now. The new schema stores when they agreed.
            ->add('agreeTerms', CheckboxType::class, [
                'mapped' => false,
                'constraints' => [
                    new Assert\IsTrue(
                        message: 'Please agree to the registration terms.'
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
