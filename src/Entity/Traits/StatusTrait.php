<?php

namespace App\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;

trait StatusTrait
{

    /**
     * @var int
     *
     * @ORM\Column(name="status", type="integer", nullable=false)
     */
    private $status = 1;

    public function getStatus(): int
    {
        return $this->status;
    }

    public function setStatus(int $status): self
    {
        $this->status = $status;

        return $this;
    }

}