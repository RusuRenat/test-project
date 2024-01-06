<?php

namespace App\Repository;

use App\Utils\Traits\Repository;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

class BooksRepository extends EntityRepository
{
    use Repository;

    public array $searchFields = [
        'title', 'author', 'description'
    ];

    public array $mainFields = [
        'id',
        'title',
        'author',
        'description',
        'price',
        'status',
        'dateUpdated',
        'dateCreated',
    ];

    public array $additionalSearchFields = [];

    public array $additionalFields = [];

}
