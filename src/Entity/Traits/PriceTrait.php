<?php

namespace App\Entity\Traits;


use Doctrine\ORM\Mapping as ORM;

trait PriceTrait
{
    /**
     * @var float|null
     *
     * @ORM\Column(name="price", type="float", precision=10, scale=2, nullable=true)
     */
    private $price;

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(?float $price): self
    {
        $this->price = $price;
        return $this;
    }
}