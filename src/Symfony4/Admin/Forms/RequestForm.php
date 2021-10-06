<?php

namespace ZnTool\Package\Symfony4\Admin\Forms;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use ZnCore\Base\Libs\I18Next\Facades\I18Next;
use ZnCore\Domain\Interfaces\Entity\ValidateEntityByMetadataInterface;
use ZnLib\Web\Symfony4\MicroApp\Interfaces\BuildFormInterface;
use ZnTool\Package\Domain\Entities\UserEntity;
use ZnTool\Package\Domain\Interfaces\Services\UserServiceInterface;

class RequestForm implements ValidateEntityByMetadataInterface, BuildFormInterface
{

    private $authBy = null;
    private $version = null;
    private $method = null;
    private $body = '{}';
    private $meta = '{}';
    private $description = null;

    private $_userService;

    public function __construct(UserServiceInterface $userService)
    {
        $this->_userService = $userService;
    }

    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
//        $metadata->addPropertyConstraint('authBy', new Assert\NotBlank());
        $metadata->addPropertyConstraint('method', new Assert\NotBlank());
        $metadata->addPropertyConstraint('version', new Assert\NotBlank());
    }

    public function buildForm(FormBuilderInterface $formBuilder)
    {
        $formBuilder->add('authBy', ChoiceType::class, [
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
        ]);
        $formBuilder->add('save', SubmitType::class, [
            'label' => I18Next::t('core', 'action.send')
        ]);
    }

    public function getAuthBy()
    {
        return $this->authBy;
    }

    public function setAuthBy($authBy): void
    {
        $this->authBy = $authBy;
    }

    public function getVersion()
    {
        return $this->version;
    }

    public function setVersion($version): void
    {
        $this->version = $version;
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function setMethod($method): void
    {
        $this->method = $method;
    }

    public function getBody()
    {
        $decoded = json_decode($this->body);
        $encoded = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        return $decoded ? $encoded : '{}';
//        return $this->body;
    }

    public function setBody($body): void
    {
        $decoded = json_decode($body);
        $encoded = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $body = $decoded ? $encoded : '{}';
        $this->body = $body;
    }

    public function getMeta()
    {
        $decoded = json_decode($this->meta);
        $encoded = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        return $decoded ? $encoded : '{}';
        //return $this->meta;
    }

    public function setMeta($meta): void
    {
        $decoded = json_decode($meta);
        $encoded = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $meta = $decoded ? $encoded : '{}';
        $this->meta = $meta;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription($description): void
    {
        $this->description = $description;
    }

    public function getUserOptions(): array
    {
        /** @var UserEntity[] $collection */
        $collection = $this->_userService->all();
        $options = [
            null => 'guest'
        ];
        foreach ($collection as $entity) {
            $options[$entity->getId()] = $entity->getLogin() ?? $entity->getDescription();
        }
        return $options;
    }

    public function getVersionOptions(): array
    {
        /** @var UserEntity[] $collection */
        $collection = $this->_userService->all();
        $options = [];
        for ($i = 1; $i <= 3; $i++) {
            $options[$i] = "Version $i";
        }
        return $options;
    }
}
