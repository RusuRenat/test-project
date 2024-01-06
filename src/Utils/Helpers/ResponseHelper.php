<?php

namespace App\Utils\Helpers;

use App\Entity\Articles;
use App\Entity\Slugs;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\HttpFoundation\Request;

class ResponseHelper
{

    public static function getResponseData(ObjectManager $em, array $entities, string $model, string $locale): array
    {
        $entitiesObj = $em->getRepository($model)->findBy(['id' => array_column($entities, 'id')]);
        $entitiesSlugsObj = $em->getRepository(Slugs::class)->findBy(['entity' => array_column($entities, 'id'), 'entityType' => $model, 'locale' => $locale]);
        $entitiesObjIndexed = ArrayHelper::getIndexedArray($entitiesObj, 'getId', true, true);
        $entitiesSlugsObjIndexed = ArrayHelper::getIndexedArray($entitiesSlugsObj, 'getEntity', object: true);
        foreach ($entities as &$entity) {
            if ($em->getClassMetadata($model)->getReflectionClass()->hasMethod('getEntityMedias')){
                $media = ArrayHelper::objectToArrayRecursive($entitiesObjIndexed[$entity['id']]->getEntityMedias($em, true, true), ['users', 'origin', 'dateUpdated', 'dateCreated', 'id', 'size']);
                $entity['media'] = $media['media'];
                $entity['media']['zone'] = $media['zone'];
            }

            if (isset($entitiesSlugsObjIndexed[$entity['id']])){
                $entity['action'] = $entitiesSlugsObjIndexed[$entity['id']][0]->getAction();
            }

        }
        unset($entity);

        return $entities;
    }
}