<?php

namespace ZnTool\Package\Domain\Libs\Deps;

use ZnCore\Collection\Interfaces\Enumerable;
use ZnCore\Instance\Helpers\ClassHelper;

class PhpClassNameParser
{

    public function parse(string $code)
    {
        $namespace = $this->parseNameSpace($code);
        $uses = $this->parseUses($code);
        $names = $this->parseNames($code);
        $classes = [];
        foreach ($names as $classItem) {
            if ($this->isExist($classItem)) {
                $classes[] = trim($classItem, '\\');
            } elseif (strpos($classItem, '\\') && $classItem[0] !== '\\') {
                $arr = explode('\\', $classItem);
                $alias = $arr[0];
                if (isset($uses[$alias])) {
                    unset($arr[0]);
                    $className = $uses[$alias] . '\\' . implode('\\', $arr);
                    if ($this->isExist($className)) {
                        $classes[] = trim($className, '\\');
                    }
                }
            } elseif ($this->isExist($namespace . '\\' . $classItem) && $classItem[0] !== '\\') {
                $classes[] = trim($namespace . '\\' . $classItem, '\\');

            }
        }
        return $classes;

//        $tokenCollection = PhpTokenHelper::getTokens($code);
//        return $this->extractClasses($tokenCollection);
    }

    private function isExist(string $classItem)
    {
        $classItem = trim($classItem, '\\');
        return class_exists($classItem) || interface_exists($classItem) || trait_exists($classItem);
    }

    private function parseNameSpace(string $code): string
    {
        $exp = 'namespace\s+(.+);';
        preg_match(
            "/$exp/i",
            $code,
            $matches
        );
        return $matches[1];
//        dd($matches);
//         ZnLib\Wsdl\Domain\Entities;
    }

    private function parseNames(string $code): array
    {
        $exp = '((?:\\\\)?[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*(\\\\[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*)*)';
        preg_match_all(
            "/$exp/i",
            $code,
            $matches
        );
        return $matches[0];
    }

    private function parseUses(string $code): array
    {
        $exp = 'use\s+(.+);';

        preg_match_all(
            "/$exp/i",
            $code,
            $matches
        );

        $uses = [];

        foreach ($matches[1] as $useItem) {
            $withAs = preg_match('/(.+)\s+as\s+(.+)/i', $useItem, $withAsMatches);
            if ($withAs) {
                $alias = $withAsMatches[2];
                $path = $withAsMatches[1];
//                dd($withAsMatches);
            } else {
                $alias = ClassHelper::getClassOfClassName($useItem);
                $path = $useItem;
            }
            $uses[$alias] = $path;
        }
        return $uses;
    }

    private function extractClasses(Enumerable $tokenCollection)
    {
        $classes = [];
        $startIndex = null;
        foreach ($tokenCollection as $index => $tokenEntity) {
            if (!$startIndex && ($tokenEntity->getName() == 'T_NS_SEPARATOR' || $tokenEntity->getName() == 'T_STRING')) {

                $className = '';
                $i = $index;
                do {
                    $tokenEntity2 = $tokenCollection[$i];
//                    dd($tokenEntity2);
                    $data = $tokenEntity2->getData();
                    if ($tokenEntity2->getName() != 'UNKNOWN') {
                        $className .= $data;
                    }
                    $i++;
                } while ($tokenEntity2->getName() != 'UNKNOWN');

//                dd($className);
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
