<?php

namespace ZnTool\Package\Domain\Libs;

use ZnCore\Arr\Helpers\ArrayHelper;
use ZnLib\Components\Store\StoreFile;
use ZnTool\Package\Domain\Entities\ConfigEntity;
use ZnTool\Package\Domain\Helpers\ComposerConfigHelper;

class Depend
{

    private $namespacesPackages = [];
    private $lastVersions = [];
    private $installedVersions = [];

    public function __construct($namespacesPackages, $lastVersions)
    {
        $this->namespacesPackages = $namespacesPackages;
        $this->lastVersions = $lastVersions;

        $store = new StoreFile(__DIR__ . '/../../../../../composer/installed.json');
        $installed = $store->load();
        foreach ($installed as $item) {
            $this->installedVersions[$item['name']] = $item['version'];
        }
    }

    private function getWanted($configEntity)
    {
        $wantedResult = [];
        $requirePackage = $this->getRequiredFromPhpCode($configEntity);
        if (!empty($requirePackage)) {
            $deps[$configEntity->getId()] = $wantedResult;
        }
        $wanted = ComposerConfigHelper::getWanted($configEntity, $requirePackage);
        foreach ($wanted as $packageId) {
            $lastVersion = ArrayHelper::getValue($this->lastVersions, $packageId);
            if (empty($lastVersion)) {
                $lastVersion = ArrayHelper::getValue($this->installedVersions, $packageId);
            }
            $lastVersion = str_replace('.x-dev', '.*', $lastVersion);
            $wantedResult[$packageId] = $lastVersion;
        }
        return $wantedResult;
    }

    public function allDepends($collection, callable $callback = null): array
    {
        $deps = [];
        foreach ($collection as $configEntity) {
            $wanted = $this->getWanted($configEntity);
            if (!empty($wanted)) {
                $deps[$configEntity->getId()] = $wanted;
            }
            if ($callback != null) {
                $callback();
            }
        }
        return $deps;
    }

    public function all($collection, callable $callback): array
    {
        $deps = [];
        $allWanted = $this->allDepends($collection);
        foreach ($collection as $configEntity) {
            $dep = $this->item($configEntity);
            $dep['wanted'] = ArrayHelper::getValue($allWanted, $configEntity->getId());
            $deps[$configEntity->getId()] = $dep;
            if ($callback != null) {
                $callback();
            }
        }
        return $deps;
    }

    private function getRequiredFromPhpCode(ConfigEntity $configEntity): array
    {
        $dir = $configEntity->getPackage()->getDirectory();
        $uses = ComposerConfigHelper::getUses($dir);
        $requirePackage = [];
        if ($uses) {
            foreach ($uses as $use) {
                foreach ($this->namespacesPackages as $namespacesPackage => $packageEntity) {
                    if (mb_strpos($use, $namespacesPackage) === 0) {
                        $requirePackage[] = $packageEntity->getId();
                    }
                }
            }
            $requirePackage = array_unique($requirePackage);
            $requirePackage = array_values($requirePackage);
        }
        return $requirePackage;
    }

    private function getRequreUpdate(array $requires)
    {
        $requireUpdate = [];
        foreach ($this->lastVersions as $packageId => $lastVersion) {
            $currentVersion = ArrayHelper::getValue($requires, $packageId);
            if ($currentVersion && $lastVersion && version_compare($currentVersion, $lastVersion, '<')) {
                $requireUpdate[$packageId] = $lastVersion;
            }
        }
    }

    private function item(ConfigEntity $configEntity)
    {
        //$dep['id'] = $configEntity->getId();
        $dep['all'] = $configEntity->getAllRequire();

        $requires = $configEntity->getAllRequire();
        if ($requires) {
            $dep['update'] = $this->getRequreUpdate($requires);
        }

        //unset($dep['require-package']);
        return $dep;
    }
}