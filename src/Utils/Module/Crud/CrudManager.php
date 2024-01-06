<?php

namespace App\Utils\Module\Crud;

use App\Entity\Categories;
use App\Entity\Media;
use App\Entity\MediaHasEntity;
use App\Entity\Slugs;
use App\Utils\Constants\Status;
use App\Utils\Helpers\ArrayHelper;
use App\Utils\Helpers\SlugHelper;
use App\Utils\Helpers\StringHelper;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\HttpFoundation\Request;

class CrudManager
{
    public function __construct(private readonly ObjectManager $em, private readonly Request $request, private mixed $model)
    {
    }

    final public function createEntity(): mixed
    {
        return $this->updateEntity();
    }

    final public function updateEntity(mixed $entity = null): mixed
    {
        if (!$entity) {
            $entity = new $this->model();
        }

        foreach ($this->request->toArray() as $name => $value) {
            if ($this->em->getClassMetadata($this->model)->getReflectionClass()->hasMethod('set' . ucfirst($name)) && StringHelper::checkAllowedType($this->em->getClassMetadata($this->model)->getReflectionClass()->getMethod('set' . ucfirst($name))->getParameters()[0]->getType())) {
                $entity->{"set" . ucfirst($name)}($value);
            }
        }

        $this->em->persist($entity);

        $this->em->flush();

        return $entity;
    }

    final public function createEntityTranslations(mixed $entity, array $languagesParams): void
    {
        $this->updateEntityTranslations($entity, $languagesParams);
    }

    final public function updateEntityTranslations(mixed $entity, array $languagesParams): void
    {

        // request method create slugs

        $slugToGenerate = "";

        $translationsClassName = $this->model . 'Translations';
        foreach ($languagesParams as $locale => $translation) {
            $entityTranslation = $this->em->getRepository($translationsClassName)->findOneBy(['entity' => $entity, 'locale' => $locale]);
            if (!$entityTranslation) {
                $entityTranslation = new $translationsClassName();
                $entityTranslation->setEntity($entity);
                $entityTranslation->setLocale($locale);
            }

            foreach ($translation as $name => $value) {
                if ($this->em->getClassMetadata($translationsClassName)->getReflectionClass()->hasMethod('set' . ucfirst($name))) {
                    $entityTranslation->{"set" . ucfirst($name)}($value);
                }
            }

            if ($this->request->get('method')) {

                $slug = $this->em->getRepository(Slugs::class)->findOneBy(['entity' => $entity->getId(), 'entityType' => $this->model, 'locale' => $locale]);

                if (!$slug) {
                    $slug = new Slugs();
                    $slug->setEntity($entity->getId());
                    $slug->setEntityType($this->model);
                    $slug->setLocale($locale);
                }

                if (!empty($translation['title'])) {
                    $slugToGenerate = $translation['title'];
                }

                if ($slugToGenerate) {
                    $generatedSlug = $this->generateSlug($slugToGenerate);
                    $slug->setSlug($generatedSlug);
                }

                $slug->setAction($this->request->get('method'));

                $this->em->persist($slug);

            }

            $this->em->persist($entityTranslation);
            $this->em->flush();
        }
    }

    final public function createEntityHasCategories(mixed $entity): void
    {
        $this->updateEntityHasCategories($entity);
    }

    final public function updateEntityHasCategories(mixed $entity): void
    {
        $categoriesId = $this->request->get('categories');

        // delete categories
        $entityHasCategoryClassName = $this->model . 'HasCategories';
        if (class_exists($entityHasCategoryClassName)) {
            $entityHasCategories = $this->em->getRepository($entityHasCategoryClassName)->findBy(['entity' => $entity]);
            if ($entityHasCategories) {
                foreach ($entityHasCategories as $entityHasCategory) {
                    if (in_array($entityHasCategory->getCategory()->getId(), $categoriesId)){
                        continue;
                    }
                    $this->em->remove($entityHasCategory);
                }
            }
        }

        if ($categoriesId) {
            foreach ($categoriesId as $id) {
                $entityCategoryName = $this->model . "Categories";
                if (!class_exists($entityCategoryName)) {
                    $entityCategoryName = Categories::class;
                }
                $category = $this->em->getRepository($entityCategoryName)->find($id);
                if ($category) {
                    if (class_exists($entityHasCategoryClassName)) {
                        $entityHasCategory = $this->em->getRepository($entityHasCategoryClassName)->findOneBy(['category' => $category, 'entity' => $entity]);
                        if (!$entityHasCategory) {
                            $entityHasCategory = new $entityHasCategoryClassName();
                            $entityHasCategory->setEntity($entity);
                            $entityHasCategory->setCategory($category);

                            $this->em->persist($entityHasCategory);
                        }
                    }
                }
            }
        }

        $this->em->flush();
    }

    final public function createEntityMedias(mixed $entity): void
    {
        $this->updateEntityMedias($entity);
    }

    final public function updateEntityMedias(mixed $entity): void
    {
        $medias = $this->request->get('medias');
        $entityMedias = $this->em->getRepository(MediaHasEntity::class)->findEntityMediasByCriteria($entity->getId(), $this->model);

        $denyDeleteEntityIds = [];
        if ($medias) {
            $denyDeleteEntityIds = array_unique(array_column($medias, 'id'));
        }

        if (array_diff(array_column($entityMedias, 'id'), $denyDeleteEntityIds)) {
            $this->em->getRepository(MediaHasEntity::class)->delete($entity->getId(), $this->model, $denyDeleteEntityIds);
        }

        if ($medias) {
            $mediaIds = array_column($medias, 'mediaId');
            $mediasObj = $this->em->getRepository(Media::class)->findBy(['id' => $mediaIds]);
            $mediasObjIndexedById = ArrayHelper::getIndexedArray($mediasObj, 'getId', true, true);

            foreach ($medias as $media) {
                if (!isset($mediasObjIndexedById[$media['mediaId']])) {
                    continue;
                }

                if (!isset($media['id'])) {
                    $mediaHasEntity = new MediaHasEntity();
                    $mediaHasEntity->setEntity($entity->getId());
                    $mediaHasEntity->setEntityType($this->model);
                } else {
                    $mediaHasEntity = $this->em->getRepository(MediaHasEntity::class)->find($media['id']);
                }

                $mediaHasEntity->setMedia($mediasObjIndexedById[$media['mediaId']]);
                $mediaHasEntity->setZone($media['zone']);

                $this->em->persist($mediaHasEntity);
            }
        }

        $this->em->flush();
    }

    final public function generateSlug(string $text): string
    {
        $slug = SlugHelper::generate($text);
        $isDuplicate = $this->isSlugDuplicate($slug);
        if ($isDuplicate) {
            $i = 1;
            while ($isDuplicate) {
                $newSlug = $slug . '-' . $i;
                $isDuplicate = $this->isSlugDuplicate($newSlug);
                if ($isDuplicate) {
                    $i++;
                } else {
                    $slug = $newSlug;
                }
            }
        }

        return $slug;
    }

    final public function isSlugDuplicate(string $slug): bool
    {
        $slugFound = $this->em->getRepository(Slugs::class)->findOneBy(['slug' => $slug]);
        if ($slugFound) {
            return true;
        }

        return false;
    }

}