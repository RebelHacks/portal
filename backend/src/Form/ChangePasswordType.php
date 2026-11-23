<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;
use Symfony\Component\Validator\Constraints as Assert;

// Settings: a new password, allowed only with the current one.
// Someone who forgot theirs uses "Forgot your password?" on the login page.
class ChangePasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('currentPassword', PasswordType::class, [
                'label' => 'Current password',
                'attr' => ['autocomplete' => 'current-password'],
                'constraints' => [
                    new Assert\NotBlank(message: 'Please enter your current password.'),
                    new UserPassword(message: 'That isn\'t your current password.'),
                ],
            ])
            ->add('plainPassword', NewPasswordType::class, [
                'first_options' => ['label' => 'New password', 'attr' => ['autocomplete' => 'new-password']],
                'second_options' => ['label' => 'Confirm new password', 'attr' => ['autocomplete' => 'new-password']],
            ]);
    }
}
