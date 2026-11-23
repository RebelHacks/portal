<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

// A resume upload. Sign-up and Settings both use it, so the rules live in one place.
class ResumeType extends AbstractType
{
    public function getParent(): string
    {
        return FileType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'label' => 'Resume',
            // the caller hands the file to UploadStorage
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
        ]);
    }
}
