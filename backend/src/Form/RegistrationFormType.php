<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

// Step 2: the names and email are already authenticated by the signed verification link.
class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('plainPassword', NewPasswordType::class)
            // School and major belong to this year's Application, not the User account.
            ->add('school', ChoiceType::class, [
                'mapped' => false,
                'label' => 'School',
                'placeholder' => 'Select your school',
                'choices' => [
                    'University of Nevada, Las Vegas (UNLV)' => 'University of Nevada, Las Vegas',
                    'University of Nevada, Reno (UNR)' => 'University of Nevada, Reno',
                    'College of Southern Nevada (CSN)' => 'College of Southern Nevada',
                    'Nevada State University' => 'Nevada State University',
                    'Truckee Meadows Community College' => 'Truckee Meadows Community College',
                    'Great Basin College' => 'Great Basin College',
                    'Western Nevada College' => 'Western Nevada College',
                    'Other / Not listed' => 'Other',
                ],
                'constraints' => [
                    new Assert\NotBlank(message: 'Please select your school.'),
                ],
            ])
            ->add('otherSchool', TextType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'Enter your school',
                'attr' => ['maxlength' => 128, 'autocomplete' => 'organization'],
                'constraints' => [new Assert\Length(max: 128)],
            ])
            ->add('major', ChoiceType::class, [
                'mapped' => false,
                'label' => 'Major',
                'placeholder' => 'Select your major',
                'choices' => [
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
                    'Other / Not listed' => 'Other',
                ],
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
                    ),
                ],
            ])
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
