<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

// Settings: the details given at sign-up, so a typo there isn't permanent.
class ProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'label' => 'First name',
                'attr' => ['autocomplete' => 'given-name', 'maxlength' => 128],
                'constraints' => [
                    new Assert\NotBlank(message: 'Please enter your first name.'),
                    new Assert\Length(max: 128),
                ],
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Last name',
                'attr' => ['autocomplete' => 'family-name', 'maxlength' => 128],
                'constraints' => [
                    new Assert\NotBlank(message: 'Please enter your last name.'),
                    new Assert\Length(max: 128),
                ],
            ]);

        // School and major belong to this year's application. Organizers and judges may not have one.
        if ($options['with_application']) {
            $builder
                ->add('school', SchoolType::class)
                ->add('major', ChoiceType::class, [
                    'label' => 'Major',
                    'placeholder' => 'Select your major',
                    'choices' => RegistrationFormType::MAJORS,
                    'constraints' => [
                        new Assert\NotBlank(message: 'Please select your major.'),
                    ],
                ])
                ->add('otherMajor', TextType::class, [
                    'required' => false,
                    'label' => 'Enter your major',
                    'attr' => ['maxlength' => 128],
                    'constraints' => [new Assert\Length(max: 128)],
                ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'with_application' => false,
            'constraints' => [new Assert\Callback(self::otherMajorIsGiven(...))],
        ]);
        $resolver->setAllowedTypes('with_application', 'bool');
    }

    // "Other" needs the major typed in, even when the browser has no JavaScript to require it.
    public static function otherMajorIsGiven(?array $profile, ExecutionContextInterface $context): void
    {
        if (($profile['major'] ?? null) === RegistrationFormType::OTHER_MAJOR && trim((string) ($profile['otherMajor'] ?? '')) === '') {
            $context->buildViolation('Please enter your major.')->atPath('[otherMajor]')->addViolation();
        }
    }
}
