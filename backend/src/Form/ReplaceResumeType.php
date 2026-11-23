<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

// Settings: a new resume in place of the one on file.
class ReplaceResumeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('resume', ResumeType::class, ['label' => 'Choose a PDF']);
    }
}
