<?php

namespace App\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;

trait AuthorTrait
{

    /**
     * @var string|null
     *
     * @ORM\Column(name="author", type="string", length=255, nullable=true)
     */
    private $author;

    public function geAuthor(): ?string
    {
        return $this->author;
    }

    public function setAuthor(?string $author): void
    {
        $this->author = $author;
    }


}