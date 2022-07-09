<?php

namespace ZnTool\Package\Domain\Libs\Deps;

class PhpNameSpaceParser
{

    public function parse(string $code)
    {
        return $this->parseNameSpace($code);
    }

    private function parseNameSpace(string $code): string
    {
        $exp = 'namespace\s+(.+);';
        preg_match(
            "/$exp/i",
            $code,
            $matches
        );
        if(!isset($matches[1])) {
            return '';
        }

        return $matches[1];
    }
}
