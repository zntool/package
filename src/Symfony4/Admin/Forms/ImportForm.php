<?php

namespace ZnTool\Package\Symfony4\Admin\Forms;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use ZnLib\Components\I18Next\Facades\I18Next;
use ZnCore\Base\Validation\Interfaces\ValidationByMetadataInterface;
use ZnLib\Web\Form\Interfaces\BuildFormInterface;
use ZnTool\Package\Domain\Entities\UserEntity;
use ZnTool\Package\Domain\Interfaces\Services\UserServiceInterface;

class ImportForm implements ValidationByMetadataInterface, BuildFormInterface
{

    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
//        $metadata->addPropertyConstraint('authBy', new Assert\NotBlank());
//        $metadata->addPropertyConstraint('method', new Assert\NotBlank());
//        $metadata->addPropertyConstraint('version', new Assert\NotBlank());
    }

    public function buildForm(FormBuilderInterface $formBuilder)
    {
        /*$formBuilder->add('authBy', ChoiceType::class, [
            'label' => 'authBy',
            'choices' => array_flip($this->getUserOptions()),
        ]);
        $formBuilder->add('version', ChoiceType::class, [
            'label' => 'version',
            'choices' => array_flip($this->getVersionOptions()),
        ]);
        $formBuilder->add('method', TextType::class, [
            'label' => 'method'
        ]);
        $formBuilder->add('body', TextareaType::class, [
            'label' => 'body'
        ]);
        $formBuilder->add('meta', TextareaType::class, [
            'label' => 'meta'
        ]);
        $formBuilder->add('description', TextareaType::class, [
            'label' => 'description'
        ]);
        $formBuilder->add('persist', SubmitType::class, [
            'label' => I18Next::t('core', 'action.save')
        ]);
        $formBuilder->add('delete', SubmitType::class, [
            'label' => I18Next::t('core', 'action.delete')
        ]);*/
        $formBuilder->add('save', SubmitType::class, [
            'label' => I18Next::t('core', 'action.send')
        ]);
    }
}
