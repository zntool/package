<?php

namespace ZnTool\Package\Domain\Libs\Deps;

use ZnCore\Code\Helpers\PhpTokenHelper;
use ZnCore\Instance\Helpers\ClassHelper;
use ZnCore\Text\Helpers\TextHelper;

class PhpClassNameInQuotedStringParser
{

    public function parse(string $code)
    {
        $classes = [];
        $tokenCollection = PhpTokenHelper::getTokens($code);
        foreach ($tokenCollection as $tokenEntity) {
            if ($tokenEntity->getName() == 'T_CONSTANT_ENCAPSED_STRING') {
                $className = $tokenEntity->getData();
                $className = trim($className, '\'"');
                $className = TextHelper::removeDoubleChar($className, '\\');
                if (ClassHelper::isExist($className)) {
                    $classes[] = $className;
                }

            }
        }
        return $classes;
    }
}
