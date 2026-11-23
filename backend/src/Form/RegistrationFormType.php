<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

// Step 2: the names and email are already authenticated by the signed verification link.
class RegistrationFormType extends AbstractType
{
    // What the major list stores for "Other / Not listed". The typed-in major replaces it before saving.
    public const OTHER_MAJOR = 'Other';

    // label => what is stored
    public const MAJORS = [
        'Computer Science' => 'Computer Science',
        'Software Engineering' => 'Software Engineering',
        'Computer Engineering' => 'Computer Engineering',
        'Electrical Engineering' => 'Electrical Engineering',
        'Mechanical Engineering' => 'Mechanical Engineering',
        'Data Science' => 'Data Science',
        'Cybersecurity' => 'Cybersecurity',
        'Information Systems / IT' => 'Information Systems / IT',
        'Mathematics / Statistics' => 'Mathematics / Statistics',
        'Physics' => 'Physics',
        'Biology / Life Sciences' => 'Biology / Life Sciences',
        'Business' => 'Business',
        'Design / Arts' => 'Design / Arts',
        'Undeclared' => 'Undeclared',
        'Other / Not listed' => self::OTHER_MAJOR,
    ];

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('plainPassword', NewPasswordType::class)
            // School and major belong to this year's Application, not the User account.
            ->add('school', SchoolType::class, ['mapped' => false])
            ->add('major', ChoiceType::class, [
                'mapped' => false,
                'label' => 'Major',
                'placeholder' => 'Select your major',
                'choices' => self::MAJORS,
                'constraints' => [
                    new Assert\NotBlank(message: 'Please select your major.'),
                ],
            ])
            ->add('otherMajor', TextType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'Enter your major',
                'attr' => ['maxlength' => 128],
                'constraints' => [new Assert\Length(max: 128)],
            ])
            ->add('attendedBefore', ChoiceType::class, [
                'mapped' => false,
                'label' => 'Have you been to RebelHacks before?',
                'choices' => ['Yes' => true, 'No' => false],
                'expanded' => true,
                'constraints' => [
                    new Assert\NotNull(message: 'Please answer yes or no.'),
                ],
            ])
            ->add('resume', ResumeType::class)
            ->add('agreeTerms', CheckboxType::class, [
                'mapped' => false,
                'constraints' => [
                    new Assert\IsTrue(message: 'Please agree to the registration terms.'),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => User::class]);
    }
}
