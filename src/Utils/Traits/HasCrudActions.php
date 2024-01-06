<?php

namespace App\Utils\Traits;

use App\Entity\Pages;
use App\Entity\Slugs;
use Doctrine\Persistence\ObjectManager;
use App\Utils\Constants\{Status, Utils};
use App\Utils\Helpers\{ArrayHelper, StringHelper};
use App\Utils\Module\Crud\CrudManager;
use Symfony\Component\HttpFoundation\{JsonResponse, Request, Response};

trait HasCrudActions
{
    public function index(Request $request): JsonResponse
    {
        $em = $this->doctrine->getManager();

        //try {

            // validate request parameters
            $errors = $this->validationManager->validate(StringHelper::snakeCaseToCamelCase($request->attributes->get('_route')));
            if (!empty($errors)) {
                return $this->setResponse($errors, Response::HTTP_BAD_REQUEST);
            }

            if (method_exists($this, 'additionalIndexValidation')) {
                $errors = $this->additionalIndexValidation($request);
                if ($errors) {
                    return $this->setResponse($errors, Response::HTTP_BAD_REQUEST);
                }
            }

            // set pagination and sorting
            $this->setListingConfigurations($request, $page, $noRecords, $sortField, $sortType);

            self::$parameters['q'] = $request->get('q');
            self::$parameters['status'] = [Status::ACTIVE, Status::INACTIVE, Status::PENDING];
            if ($request->get('status')) {
                self::$parameters['status'] = explode(',', $request->get('status') ?? '');
            }

            $noTotal = $em->getRepository(self::$model)->getCount(self::$parameters);
            $lists = $em->getRepository(self::$model)->getAll($page, $noRecords, $sortField, $sortType, self::$parameters);

            // filter return fields
            $lists = ArrayHelper::formatDates($lists);
            $lists = ArrayHelper::filterArrayByKeys($lists, $request->get('fields'));

            // create header link
            $headerLink = $this->setHeaderLink($request, $page, $noRecords, $noTotal);

            // return response
            return $this->setResponse($lists, Response::HTTP_OK, ['X-Total-Count' => $noTotal, 'Link' => $headerLink]);
        //} catch (\Exception $e) {
        //    $this->logAPIError($e);
        //
        //    return $this->setResponse(Utils::INTERNAL_SERVER_ERROR, Response::HTTP_INTERNAL_SERVER_ERROR);
        //}

    }

    public function create(Request $request): JsonResponse
    {
        $em = $this->doctrine->getManager();
        $em->getConnection()->beginTransaction();

        try {

            // validate request parameters
            $errors = $this->validationManager->validate(StringHelper::snakeCaseToCamelCase($request->attributes->get('_route')));
            if (!empty($errors)) {
                return $this->setResponse($errors, Response::HTTP_BAD_REQUEST);
            }

            $user = $this->detectUser();
            if (!$user) {
                return $this->setResponse(Utils::USER_NOT_FOUND, Response::HTTP_BAD_REQUEST);
            }

            if (method_exists($this, 'additionalCreateValidation')) {
                $errors = $this->additionalCreateValidation($request);
                if ($errors) {
                    return $this->setResponse($errors, Response::HTTP_BAD_REQUEST);
                }
            }

            $crudManager = new CrudManager($em, $request, self::$model);
            $entity = $crudManager->createEntity();

            // If entity has own manager, call the create function inside it
            $classShortName = $em->getClassMetadata(self::$model)->getReflectionClass()->getShortName();
            $entityManagerName = 'App\Utils\Module\\' . $classShortName . '\\' . $classShortName . 'Manager';
            if (class_exists($entityManagerName)) {
                $entityManager = new $entityManagerName($em, $request, self::$model, $entity);
                $entityManager->create();
            }

            // commit transaction
            $em->getConnection()->commit();

            $response = ArrayHelper::objectToArrayRecursive($entity, self::$elementToExclude);

            return $this->setResponse($response, Response::HTTP_CREATED);
        } catch (\Exception $e) {
            $this->logAPIError($e);

            $em->getConnection()->rollBack();

            return $this->setResponse(Utils::INTERNAL_SERVER_ERROR, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show(Request $request, mixed $id): JsonResponse
    {

        $em = $this->doctrine->getManager();

        try {

            // validate request parameters
            $errors = $this->validationManager->validate(StringHelper::snakeCaseToCamelCase($request->attributes->get('_route')));
            if (!empty($errors)) {
                return $this->setResponse($errors, Response::HTTP_BAD_REQUEST);
            }

            $user = $this->detectUser();
            if (!$user) {
                return $this->setResponse(Utils::USER_NOT_FOUND, Response::HTTP_BAD_REQUEST);
            }

            $criteria = ['id' => $id];
            if (in_array('status', $em->getClassMetadata(self::$model)->fieldNames)) {
                $criteria['status'] = [Status::ACTIVE, Status::PENDING, Status::INACTIVE];
            }

            $entity = $em->getRepository(self::$model)->findOneBy($criteria);
            if (!$entity) {
                return $this->setResponse(Utils::ENTITY_NOT_FOUND, Response::HTTP_BAD_REQUEST);
            }

            $response = ArrayHelper::objectToArrayRecursive($entity, self::$elementToExclude);

            if (method_exists($this, 'additionalShowResponse')) {
                $this->additionalShowResponse($response, $entity);
            }

            // return response
            return $this->setResponse($response);
        } catch (\Exception $e) {
            $this->logAPIError($e);

            return $this->setResponse(Utils::INTERNAL_SERVER_ERROR, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(Request $request, mixed $id): JsonResponse
    {

        $em = $this->doctrine->getManager();
        $em->getConnection()->beginTransaction();

        try {

            // validate request parameters
            $errors = $this->validationManager->validate(StringHelper::snakeCaseToCamelCase($request->attributes->get('_route')));
            if (!empty($errors)) {
                return $this->setResponse($errors, Response::HTTP_BAD_REQUEST);
            }

            $user = $this->detectUser();
            if (!$user) {
                return $this->setResponse(Utils::USER_NOT_FOUND, Response::HTTP_BAD_REQUEST);
            }

            $entity = $em->getRepository(self::$model)->findOneBy(['id' => $id, 'status' => [Status::ACTIVE, Status::PENDING, Status::INACTIVE]]);
            if (!$entity) {
                return $this->setResponse(Utils::ENTITY_NOT_FOUND, Response::HTTP_BAD_REQUEST);
            }

            if (method_exists($this, 'additionalUpdateValidation')) {
                $errors = $this->additionalUpdateValidation($request, $id);
                if ($errors) {
                    return $this->setResponse($errors, Response::HTTP_BAD_REQUEST);
                }
            }

            $crudManager = new CrudManager($em, $request, self::$model);
            $entity = $crudManager->updateEntity($entity);

            // If entity has own manager, call the create function inside it
            $classShortName = $em->getClassMetadata(self::$model)->getReflectionClass()->getShortName();
            $entityManagerName = 'App\Utils\Module\\' . $classShortName . '\\' . $classShortName . 'Manager';
            if (class_exists($entityManagerName)) {
                $entityManager = new $entityManagerName($em, $request, self::$model, $entity);
                $entityManager->update();
            }

            // commit transaction
            $em->getConnection()->commit();

            return $this->show($request, $id);
        } catch (\Exception $e) {
            $this->logAPIError($e);

            $em->getConnection()->rollBack();

            return $this->setResponse(Utils::INTERNAL_SERVER_ERROR, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function delete(Request $request, mixed $id): JsonResponse
    {
        $em = $this->doctrine->getManager();
        $em->getConnection()->beginTransaction();

        try {

            // validate request parameters
            $errors = $this->validationManager->validate(StringHelper::snakeCaseToCamelCase($request->attributes->get('_route')));
            if (!empty($errors)) {
                return $this->setResponse($errors, Response::HTTP_BAD_REQUEST);
            }

            $user = $this->detectUser();
            if (!$user) {
                return $this->setResponse(Utils::USER_NOT_FOUND, Response::HTTP_BAD_REQUEST);
            }

            $entity = $em->getRepository(self::$model)->findOneBy(['id' => $id, 'status' => [Status::ACTIVE, Status::PENDING, Status::INACTIVE]]);
            if (!$entity) {
                return $this->setResponse(Utils::ENTITY_NOT_FOUND, Response::HTTP_BAD_REQUEST);
            }

            if (method_exists($this, 'additionalDeleteValidation')) {
                $errors = $this->additionalDeleteValidation($request, $id);
                if ($errors) {
                    return $this->setResponse($errors, Response::HTTP_BAD_REQUEST);
                }
            }

            $entity->setStatus(Status::DELETED);

            if (method_exists($this, 'additionalDeleteFunctionalities')) {
                $this->additionalDeleteFunctionalities($request, $id);
            }

            $em->persist($entity);
            $em->flush();

            // commit transaction
            $em->getConnection()->commit();

            // return response
            return $this->setResponse([], Response::HTTP_NO_CONTENT);
        } catch (\Exception $e) {
            $this->logAPIError($e);

            $em->getConnection()->rollBack();

            return $this->setResponse(Utils::INTERNAL_SERVER_ERROR, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

}