<?php

namespace App\Utils\Traits;

use App\Entity\Slugs;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query\Expr\Select;
use Doctrine\ORM\QueryBuilder;

trait Repository
{

    public $aliasName;

    public $isSecuredRoute = true;

    final public function __construct(EntityManagerInterface $em, ClassMetadata $class)
    {
        parent::__construct($em, $class);
        $this->aliasName = lcfirst(str_replace('App\Entity\\', '', $this->getClassName()));
    }

    final public function setSecuredRoute(bool $isSecuredRoute): void
    {
        $this->isSecuredRoute = $isSecuredRoute;
    }

    final public function getCount(array $parameters = []): int
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $this->commonQuery($qb, $parameters);
        $qb->select("count({$this->aliasName}.id)");

        try {
            return $qb->getQuery()->getSingleScalarResult();
        } catch (\Exception $e) {
        }

        return count($qb->getQuery()->getResult());
    }

    /**
     * @param QueryBuilder $qb
     * @param array $parameters
     * @return void
     */
    final public function commonQuery(QueryBuilder $qb, array $parameters = []): void
    {
        $qb->from($this->getClassName(), $this->aliasName);

        if (method_exists($this, 'additionalQuery')) {
            $this->additionalQuery($qb, $parameters);
        }

        if (isset($parameters['id']) && $parameters['id']) {
            $qb->andWhere($this->aliasName . '.id = :id');
            $qb->setParameter('id', $parameters['id']);
        }

        if (isset($parameters['status']) && count($parameters['status']) > 0 && $parameters['status'][0] !== '' && in_array('status', $this->getClassMetadata()->fieldNames)) {
            if (count($parameters['status']) > 1) {
                $qb->andWhere($this->aliasName . '.status IN (:status)');
                $qb->setParameter('status', $parameters['status'], ArrayParameterType::INTEGER);
            } else {
                $qb->andWhere($this->aliasName . '.status = :status');
                $qb->setParameter('status', $parameters['status'][0]);
            }
        }

        if (isset($parameters['q']) && ($this->searchFields || $this->additionalSearchFields)) {
            $orX = $qb->expr()->orX();
            $i = 0;
            foreach ($this->searchFields as $field) {
                if (in_array($field, $this->getClassMetadata()->fieldNames)) {
                    $orX->add($qb->expr()->like($this->aliasName . '.' . $field, ':q' . $i));

                    $qb->setParameter('q' . $i, '%' . $parameters['q'] . '%');
                    $i++;
                }
            }

            if ($this->additionalSearchFields) {
                foreach ($this->additionalSearchFields as $field) {
                    $orX->add($qb->expr()->like($field, ':q' . $i));
                    $qb->setParameter('q' . $i, '%' . $parameters['q'] . '%');
                    $i++;
                }
            }
            $qb->andWhere($orX);
        }
    }

    /**
     * @param int|null $page
     * @param int|null $noRecords
     * @param string|null $sortField
     * @param string|null $sortType
     * @param array $parameters
     * @return array
     */
    final public function getAll(?int $page = null, ?int $noRecords = null, ?string $sortField = null, ?string $sortType = null, array $parameters = []): array
    {

        $qb = $this->getBuilder();

        if (method_exists($this, 'additionalSelect')) {
            $this->additionalSelect($qb);
        }

        $this->commonQuery($qb, $parameters);

        if (in_array($sortField, $this->getClassMetadata()->fieldNames)) {
            $qb->orderBy($this->aliasName . '.' . $sortField, $sortType);
        } elseif ($this->additionalFields && array_key_exists($sortField, $this->additionalFields)) {
            $qb->orderBy($this->additionalFields[$sortField], $sortType);
        } else {
            $qb->orderBy($this->aliasName . '.id', $sortType);
        }

        $qb->setMaxResults($noRecords);
        $qb->setFirstResult($page * $noRecords);

        return $qb->getQuery()->getResult();
    }

    /**
     * @return QueryBuilder
     */
    final public function getBuilder(): QueryBuilder
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $selectValue = [$this->aliasName . '.id'];
        foreach ($this->mainFields as $field) {
            if ($field === 'id') {
                continue;
            }
            $selectValue[] = $this->aliasName . '.' . $field;
        }

        $qb->select(implode(',', $selectValue));

        return $qb;
    }

}