<?php

namespace ZnTool\Package\Domain\Entities;

use ZnDomain\Entity\Interfaces\EntityIdInterface;

class GitEntity implements EntityIdInterface
{

    private $id;
    private $directory;
    private $commits;
    private $tags;

    public function getId()
    {
        return $this->id;
    }

    public function setId($id): void
    {
        $this->id = $id;
    }

    public function getDirectory()
    {
        return $this->directory;
    }

    public function setDirectory($directory): void
    {
        $this->directory = $directory;
    }

    public function getCommits()
    {
        return $this->commits;
    }

    public function setCommits($commits): void
    {
        $this->commits = $commits;
    }

    public function getTags()
    {
        return $this->tags;
    }

    public function setTags($tags): void
    {
        $this->tags = $tags;
    }

}
