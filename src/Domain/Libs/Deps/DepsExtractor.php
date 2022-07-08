<?php

namespace ZnTool\Package\Domain\Libs\Deps;

use ZnCore\Arr\Helpers\ArrayHelper;
use ZnCore\Code\Entities\PhpTokenEntity;
use ZnCore\Code\Helpers\PhpTokenHelper;
use ZnCore\Collection\Interfaces\Enumerable;

class DepsExtractor
{

    public function extractUses(string $code) {
        $tokenCollection = PhpTokenHelper::getTokens($code);
        return $this->extractUse($tokenCollection);
    }

    private function extractUse(Enumerable $tokenCollection)
    {
        $classes = [];
        foreach ($tokenCollection as $index => $tokenEntity) {
            $isClass = $this->isInClass($tokenEntity);
            if (!$isClass) {
                if ($tokenEntity->getName() == 'T_USE') {
                    $className = '';
                    $i = $index + 2;
                    do {
                        $tokenEntity2 = $tokenCollection[$i];
                        $data = $tokenEntity2->getData();
                        if($data != ';') {
                            $className .= $data;
                        }
                        $i++;
                    } while($data != ';');

                    preg_match('/(.+)\s+as\s+(.+)/i', $className, $matches);
                    if($matches) {
                        $name = $matches[2];
                        $className = $matches[1];
                    } else {
                        preg_match('/(.+)\\\(.+)/i', $className, $matches);
                        if($matches) {
                            $name = $matches[2];
                        }
                    }

                    $classes[$name] = $className;
                }
            }
        }
        return $classes;
    }

    private function isInClass(PhpTokenEntity $tokenEntity)
    {
        static $isClass = false;
        if (!$isClass && $tokenEntity->getName() == 'T_CLASS') {
            $isClass = 1;
        }
        if ($isClass && trim($tokenEntity->getData()) == '{') {
            $isClass++;
        }
        if ($isClass && trim($tokenEntity->getData()) == '}') {
            $isClass--;
            if ($isClass === 1) {
                $isClass = false;
            }
        }
        return $isClass !== false;
    }
}
