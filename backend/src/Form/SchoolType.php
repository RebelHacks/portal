<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

// A school, typed freely so any institution can register. Sign-up and Settings both use it.
// The page must include registration/_school_suggestions.html.twig for the suggestions.
// They are suggestions, NOT an allow-list.
class SchoolType extends AbstractType
{
    public function getParent(): string
    {
        return TextType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'label' => 'School / university',
            'trim' => true,
            'attr' => [
                'list' => 'school-suggestions',
                'placeholder' => 'Search or enter your school',
                'maxlength' => 128,
                'autocomplete' => 'off',
            ],
            'constraints' => [
                new Assert\NotBlank(message: 'Please enter your school.'),
                new Assert\Length(max: 128),
            ],
        ]);
    }
}
