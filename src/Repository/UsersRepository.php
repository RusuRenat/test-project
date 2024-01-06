<?php

namespace App\Repository;

use App\Utils\Traits\Repository;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

class UsersRepository extends EntityRepository
{
    use Repository;

    public array $searchFields = [
        'username', 'email'
    ];

    public array $mainFields = [
        'id',
        'email',
        'accessToken',
        'status',
        'dateUpdated',
        'dateCreated',
    ];

    public array $additionalSearchFields = [
        'usersProfiles.fullName'
    ];

    public array $additionalFields = [];

    final public function additionalQuery(QueryBuilder $qb, array $parameters = []): void
    {
        $qb->leftJoin('App:UsersProfiles', 'usersProfiles', 'WITH', $this->aliasName . '.id = usersProfiles.users');
    }

    final public function additionalSelect(QueryBuilder $qb): void
    {
        $qb->addSelect('usersProfiles.fullName');
    }

    final public function getUsersProfilesIdsByRoles(?string $roles = null): array
    {

        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select("
            usersProfiles.id as id
        ");

        $qb->from('App:Users', 'users');

        $qb->leftJoin('App:UsersProfiles', 'usersProfiles', 'WITH', 'usersProfiles.users = users.id');

        if ($roles) {
            $qb->where('users.roles LIKE :roles');
            $qb->setParameter('roles', '%"' . $roles . '"%');
        }

        return $qb->getQuery()->getArrayResult();
    }

}
