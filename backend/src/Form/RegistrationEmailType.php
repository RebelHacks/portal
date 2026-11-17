<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

// Step 1 of sign-up: only the address. The rest is RegistrationFormType, reached from the emailed link.
class RegistrationEmailType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('email', EmailType::class, [
            'label' => 'University email',
            'attr' => [
                'autocomplete' => 'email',
                'placeholder' => 'student@unlv.nevada.edu',
                'autofocus' => true,
            ],
            'constraints' => [
                new Assert\NotBlank(message: 'Please enter your university email.'),
                new Assert\Email(message: 'Please enter a valid email address.'),
                new Assert\Regex(
                    pattern: '/@(?:[a-z0-9-]+\.)+edu$/i',
                    message: 'Please use a university email address ending in .edu.'
                ),
                // the most user.email holds
                new Assert\Length(max: 180),
            ],
        ]);
    }
}
