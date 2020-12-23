<?php

namespace ZnTool\Package\Domain\Entities;

class CommitEntity
{

    private $sha;
    private $author;
    private $date;
    private $message;
    private $merge;

    public function getSha()
    {
        return $this->sha;
    }

    public function setSha($sha): void
    {
        $this->sha = $sha;
    }

    public function getAuthor()
    {
        return $this->author;
    }

    public function setAuthor($author): void
    {
        $this->author = $author;
    }

    public function getDate()
    {
        return $this->date;
    }

    public function setDate($date): void
    {
        $this->date = $date;
    }

    public function getMessage()
    {
        return $this->message;
    }

    public function setMessage($message): void
    {
        $this->message = $message;
    }

    public function getMerge()
    {
        return $this->merge;
    }

    public function setMerge($merge): void
    {
        $this->merge = $merge;
    }

}
