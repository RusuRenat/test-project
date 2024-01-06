<?php

namespace App\Entity;

use App\Entity\Traits\AuthorTrait;
use App\Entity\Traits\DescriptionTrait;
use App\Entity\Traits\IdTrait;
use App\Entity\Traits\PriceTrait;
use App\Entity\Traits\StatusTrait;
use App\Entity\Traits\TimeStampTrait;
use App\Entity\Traits\TitleTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * Books
 *
 * @ORM\Table(name="books")
 * @ORM\Entity(repositoryClass="App\Repository\BooksRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Books
{
    use IdTrait, TimeStampTrait, TitleTrait, DescriptionTrait, PriceTrait, StatusTrait, AuthorTrait;
}