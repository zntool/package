<?php

namespace ZnTool\Package\Domain\Libs\Deps;

use ZnCore\Code\Helpers\PhpHelper;
use ZnCore\Code\Helpers\PhpTokenHelper;
use ZnCore\Instance\Helpers\ClassHelper;
use ZnCore\Text\Helpers\TextHelper;

class PhpNameParser
{

    public function parse(string $code)
    {
        return $this->parseNames($code);
    }

    private function parseNames(string $code): array
    {
        $exp = '((?:\\\\)?[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*(\\\\[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*)*)';
        preg_match_all(
            "/$exp/i",
            $code,
            $matches
        );
        $names = [];
        foreach ($matches[0] as $item) {
            $isValidName = preg_match('/[a-z0-9_\\\\]+/i', $item);
            $isAllow = ! PhpHelper::isReservedName($item);
            if($isValidName && $isAllow) {
                $names[] = $item;
            }
        }

        $names = array_unique($names);
        $names = array_values($names);
        sort($names);

        return $names;
    }
}
