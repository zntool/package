<?php

namespace ZnTool\Package\Domain\Libs\Deps;

use ZnCore\Code\Entities\PhpTokenEntity;
use ZnCore\Instance\Helpers\ClassHelper;

class PhpUsesParser
{

    public function parse(string $code) {
        $code = preg_replace(
            "/class\s+[\s\S]+/i",
            '',
            $code
        );
        return $this->parseUses($code);
    }

    public function removeUses(string $code): string {
        $exp = 'use\s+(.+);';
        $code = preg_replace(
            "/$exp/i",
            '',
            $code
        );
        return $code;
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
            } else {
                $alias = ClassHelper::getClassOfClassName($useItem);
                $path = $useItem;
            }

            $uses[$alias] = $path;
        }
        return $uses;
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
