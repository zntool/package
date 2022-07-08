<?php

namespace ZnTool\Package\Domain\Libs\Deps;

use ZnCore\Arr\Helpers\ArrayHelper;
use ZnCore\Code\Entities\PhpTokenEntity;
use ZnCore\Code\Helpers\PhpTokenHelper;
use ZnCore\Collection\Interfaces\Enumerable;

class PhpClassNameParser
{

    public function parse(string $code) {
        $tokenCollection = PhpTokenHelper::getTokens($code);
        return $this->extractClasses($tokenCollection);
    }

    private function extractClasses(Enumerable $tokenCollection)
    {
        $classes = [];
        $startIndex = null;
        foreach ($tokenCollection as $index => $tokenEntity) {
            if (!$startIndex && $tokenEntity->getName() == 'T_NS_SEPARATOR') {

                $className = '';
                $i = $index + 2;
                do {
                    $tokenEntity2 = $tokenCollection[$i];
                    $data = $tokenEntity2->getData();
                    if($tokenEntity2->getName() != 'UNKNOWN') {
                        $className .= $data;
                    }
                    $i++;
                } while($tokenEntity2->getName() != 'UNKNOWN');

                dd($className);
                $classes[] = $className;

//                $startIndex = $index;
            }
            /*if ($startIndex) {
                if ($tokenEntity->getName() == 'T_NS_SEPARATOR' || $tokenEntity->getName() == 'T_STRING') {

                } else {
                    $className = '';
                    for ($i = $startIndex - 1; $i < $index; $i++) {
                        if ($tokenCollection[$i]->getName() != 'UNKNOWN') {
                            $className .= $tokenCollection[$i]->getData();
                        }
                    }
                    $classes[] = $className;
                    $startIndex = null;
                }
            }*/
        }
        return $classes;
    }
}
