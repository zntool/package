<?php

/**
 * @var $baseUri string
 * @var $this View
 * @var $entity EntityIdInterface
 */

use ZnCore\Base\Text\Helpers\TextHelper;
use ZnTool\Package\Domain\Entities\ApiKeyEntity;

use ZnLib\Components\I18Next\Facades\I18Next;
use ZnCore\Domain\Entity\Interfaces\EntityIdInterface;
use ZnLib\Web\Controller\Helpers\ActionHelper;
use ZnLib\Web\View\Libs\View;
use ZnLib\Web\TwBootstrap\Widgets\Detail\DetailWidget;
use ZnLib\Web\TwBootstrap\Widgets\Format\Formatters\ActionFormatter;
use ZnLib\Web\TwBootstrap\Widgets\Format\Formatters\LinkFormatter;

$attributes = [
    [
        'label' => 'ID',
        'attributeName' => 'id',
    ],
    [
        'label' => I18Next::t('core', 'main.attribute.applicationId'),
        'attributeName' => 'application.title',
        'sort' => true,
        'formatter' => [
            'class' => LinkFormatter::class,
            'linkAttribute' => 'application.id',
            'uri' => '/application/application/view',
        ],
    ],
    [
        'label' => I18Next::t('core', 'main.attribute.value'),
//        'attributeName' => 'value',
        'value' => function(ApiKeyEntity $apiKeyEntity) {
            return TextHelper::mask($apiKeyEntity->getValue(), 3);
        },
    ],
    [
        'label' => I18Next::t('core', 'main.attribute.created_at'),
        'attributeName' => 'createdAt',
    ],
    [
        'label' => I18Next::t('core', 'main.attribute.expired_at'),
        'attributeName' => 'expiredAt',
    ],
];

?>

<div class="row">
    <div class="col-lg-12">

        <?= DetailWidget::widget([
            'entity' => $entity,
            'attributes' => $attributes,
        ]) ?>

        <div class="float-left111">
            <?= ActionHelper::generateUpdateAction($entity, $baseUri, ActionHelper::TYPE_BUTTON) ?>
            <?= ActionHelper::generateDeleteAction($entity, $baseUri, ActionHelper::TYPE_BUTTON) ?>
        </div>

    </div>
</div>
