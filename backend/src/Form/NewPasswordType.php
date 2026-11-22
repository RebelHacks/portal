<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

// A password typed twice. Sign-up and "set a new password" both use it, so the rules live in one place.
class NewPasswordType extends AbstractType
{
    public function getParent(): string
    {
        return RepeatedType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'type' => PasswordType::class,
            // the caller hashes it; the plain text is never saved
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
                new Assert\Length(min: 8, max: 4096),
                new Assert\Regex(
                    pattern: '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9\s])\S+$/',
                    message: 'Password must contain uppercase, lowercase, a number, and a special character, with no spaces.'
                ),
            ],
        ]);
    }
}
