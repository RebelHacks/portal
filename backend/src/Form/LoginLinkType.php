<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

// The address to email a login link to. Any account's address, so no .edu rule: organizers have other ones.
class LoginLinkType extends AbstractType
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
                new Assert\NotBlank(message: 'Please enter your email.'),
                new Assert\Email(message: 'Please enter a valid email address.'),
                // the most user.email holds
                new Assert\Length(max: 180),
            ],
        ]);
    }
}
