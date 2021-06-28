<?php

namespace ZnTool\Package;

use ZnCore\Base\Libs\App\Base\BaseBundle;

class Bundle extends BaseBundle
{

    public function deps(): array
    {
        return [
            new \ZnSandbox\Sandbox\Bundle\Bundle(['all']),
        ];
    }

    public function console(): array
    {
        return [
            'ZnTool\Package\Commands',
        ];
    }

    public function container(): array
    {
        return [
            __DIR__ . '/Domain/config/container.php',
        ];
    }
}
