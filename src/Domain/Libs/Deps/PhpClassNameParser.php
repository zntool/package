<?php

namespace ZnTool\Package\Domain\Libs\Deps;

use ZnCore\Instance\Helpers\ClassHelper;

class PhpClassNameParser
{

    public function parse(string $code)
    {
        $nameSpaceParser = new PhpNameSpaceParser();
        $usesParser = new PhpUsesParser();
        $nameParser = new PhpNameParser();

        $namespace = $nameSpaceParser->parse($code);
        $uses = $usesParser->parse($code);
        $names = $nameParser->parse($code);

        $classes = [];
        foreach ($names as $classItem) {
            if (ClassHelper::isExist($classItem)) {
                $classes[] = trim($classItem, '\\');
            } elseif (strpos($classItem, '\\') && $classItem[0] !== '\\') {
                $arr = explode('\\', $classItem);
                $alias = $arr[0];
                if (isset($uses[$alias])) {
                    unset($arr[0]);
                    $className = $uses[$alias] . '\\' . implode('\\', $arr);
                    if (ClassHelper::isExist($className)) {
                        $classes[] = trim($className, '\\');
                    }
                }
            } elseif (ClassHelper::isExist($namespace . '\\' . $classItem) && $classItem[0] !== '\\') {
                $classes[] = trim($namespace . '\\' . $classItem, '\\');
            }
        }
        return $classes;
    }
}
