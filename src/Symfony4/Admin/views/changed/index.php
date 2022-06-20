<?php

/**
 * @var $this \ZnLib\Web\View\View
 * @var $formView FormView|AbstractType[]
 * @var $formRender \ZnLib\Web\Symfony4\MicroApp\Libs\FormRender
 * @var $dataProvider DataProvider
 * @var $baseUri string
 * @var $packageCollection \Illuminate\Support\Collection | \ZnSandbox\Sandbox\Bundle\Domain\Entities\BundleEntity[]
 */

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormView;
use ZnCore\Base\Libs\DataProvider\Libs\DataProvider;
use ZnTool\Package\Domain\Entities\ApiKeyEntity;

//dd($this->translate('core', 'action.send'));
?>

<div class="row">
    <div class="col-lg-12">
        <div class="list-group">
            <?php foreach ($packageCollection as $packageEntity): ?>
            <a href="<?= $this->url('package/changed/view', ['id' => $packageEntity->getId()]) ?>" class="list-group-item list-group-item-action ">
                <?= $packageEntity->getId() ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>
