<?php

namespace ZnTool\Package\Domain\Entities;

class TagEntity
{

    private $name;
    private $sha;

    public function getName()
    {
        return $this->name;
    }

    public function setName($name): void
    {
        $this->name = $name;
    }

    public function getSha()
    {
        return $this->sha;
    }

    public function setSha($sha): void
    {
        $this->sha = $sha;
    }

}
