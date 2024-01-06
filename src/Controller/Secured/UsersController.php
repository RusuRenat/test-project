<?php

namespace App\Controller\Secured;

use App\Controller\ApiController;
use App\Entity\Media;
use App\Entity\Users;
use App\Entity\UsersProfiles;
use App\Utils\Constants\Status;
use App\Utils\Constants\Utils;
use App\Utils\Helpers\ArrayHelper;
use App\Utils\Module\Email\EmailManager;
use App\Utils\Module\Media\MediaManager;
use App\Utils\Module\Security\SecurityManager;
use App\Utils\Module\Users\UsersManager;
use App\Utils\Validation\ValidationManager;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use FOS\RestBundle\Controller\Annotations\Delete;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Put;
use Gesdinet\JWTRefreshTokenBundle\Doctrine\RefreshTokenManager;
use Gesdinet\JWTRefreshTokenBundle\Generator\RefreshTokenGeneratorInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTManager;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UsersController extends ApiController
{
    public function __construct(Logger $logger, JWTManager $jwtManager, RefreshTokenManager $refreshTokenManager, RefreshTokenGeneratorInterface $refreshTokenGenerator, ValidationManager $validationManager, SecurityManager $securityManager, EmailManager $emailManager, MediaManager $mediaManager, ManagerRegistry $doctrine, UserPasswordHasherInterface $passwordHasher)
    {
        parent::__construct($logger, $jwtManager, $refreshTokenManager, $refreshTokenGenerator, $validationManager, $securityManager, $emailManager, $mediaManager, $doctrine, $passwordHasher);
        parent::$model = Users::class;
    }

    /**
     * Get users.
     *
     * Please use this service to get users.
     *
     * @OA\Parameter(name="roles", in="query", example="ADMIN", description="User role", @OA\Schema(type="string"))
     * @OA\Parameter(name="status", in="query", example="1", description="Status comma-separed", @OA\Schema(type="string"))
     * @OA\Parameter(name="q", in="query", description="Search term", @OA\Schema(type="string"))
     * @OA\Parameter(name="fields", in="query", description="Fields to return, comma separated values: field1,field2", @OA\Schema(type="string"))
     * @OA\Parameter(name="sort", in="query", example="id", description="Sort options: fieldName or -fieldName", @OA\Schema(type="string"))
     * @OA\Parameter(name="offset", in="query", example=1, description="Offset for pagination", @OA\Schema(type="integer"))
     * @OA\Parameter(name="limit", in="query", example=20, description="Limit for pagination", @OA\Schema(type="integer"))
     *
     * @OA\Response(response=200, description="Returns profile details", @Model(type=Users::class))
     * @OA\Response(response=400, description="Bad Request", @OA\JsonContent(type="object", @OA\Property(property="errors", type="array", @OA\Items(type="object", @OA\Property(property="id", type="string", example="Bad Request", description="Bad Request")))))
     * @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Unauthorized", description="Unauthorized")))
     * @OA\Response(response=403, description="Forbidden", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Forbidden", description="Forbidden")))
     * @OA\Response(response=404, description="Not found", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Not found", description="Not found")))
     * @OA\Response(response=409, description="Conflict", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Conflict", description="Conflict")))
     * @OA\Response(response=500, description="Internal Server Error", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Internal Server Error", description="Internal Server Error")))
     *
     * @OA\Tag(name="Users SECURED")
     * @Security(name="JWT")
     *
     * @Get("/secured/users", name="get_users_secured", options={ "method_prefix" = false })
     *
     */
    final public function index(Request $request): JsonResponse
    {
        if ($request->get('roles')) {
            parent::$parameters['roles'] = $request->get('roles');
        }
        return parent::index($request);
    }

    /**
     * Create user.
     *
     * Please use this service to create a user.
     *
     * @OA\RequestBody(
     *     description="User data",
     *     required=true,
     *     @OA\JsonContent(
     *          required={"email", "firstName", "lastName", "phoneNumber", "password", "confirmPassword"},
     *          @OA\Property(property="email", type="string", example="mike.smith@domain.com", description="User email"),
     *          @OA\Property(property="firstName", type="string", example="Mike Smith", description="First name"),
     *          @OA\Property(property="lastName", type="string", example="Mike Smith", description="Last name"),
     *          @OA\Property(property="phoneNumber", type="string", example="+1333444333", description="User phone number"),
     *          @OA\Property(property="password", type="string", example="MyPass123@", description="User's password"),
     *          @OA\Property(property="confirmPassword", type="string", example="MyPass123@", description="Confirm Password field"),
     *          @OA\Property(property="roles", type="string", example="ADMIN", description="User role"),
     *     )
     * )
     * @OA\Response(
     *     response=201, description="Returns user data",
     *     @OA\JsonContent(
     *         type="object",
     *         @OA\Property(property="id", type="integer", example=1, description="User unique identifier"),
     *         @OA\Property(property="username", type="string", example="mike.smith@domain.com", description="Username")
     *    )
     * )
     *
     * @OA\Response(response=204, description="No Content")
     * @OA\Response(response=400, description="Bad Request", @OA\JsonContent(type="object", @OA\Property(property="errors", type="array", @OA\Items(type="object", @OA\Property(property="id", type="string", example="Bad Request", description="Bad Request")))))
     * @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Unauthorized", description="Unauthorized")))
     * @OA\Response(response=403, description="Forbidden", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Forbidden", description="Forbidden")))
     * @OA\Response(response=404, description="Not found", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Not found", description="Not found")))
     * @OA\Response(response=409, description="Conflict", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Conflict", description="Conflict")))
     * @OA\Response(response=500, description="Internal Server Error", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Internal Server Error", description="Internal Server Error")))
     *
     * @OA\Tag(name="Users SECURED")
     * @Security(name="JWT")
     *
     * @Post("/secured/users", name="create_user_secured", options={ "method_prefix" = false })
     *
     */
    final public function create(Request $request): JsonResponse
    {

        $em = $this->doctrine->getManager();
        $em->getConnection()->beginTransaction();

        try {

            // validate request parameters
            $errors = $this->validationManager->validate('createUserSecured');
            if (!empty($errors)) {
                return $this->setResponse($errors, Response::HTTP_BAD_REQUEST);
            }

            // check if the passwords match and (re)write error message
            if (strcmp($request->get('password'), $request->get('confirmPassword')) !== 0) {
                return $this->setResponse(Utils::PASSWORD_NOT_MATCH, Response::HTTP_CONFLICT);
            }

            $user = $em->getRepository(Users::class)->findOneBy(['username' => $request->get('email')]);
            if ($user) {
                return $this->setResponse(Utils::USER_EXISTS, Response::HTTP_CONFLICT);
            }

            $userManager = new UsersManager($em, $request, $this->passwordHasher);
            $user = $userManager->createUser();
            $userProfile = $userManager->createUserProfile($user);

            // commit transaction
            $em->getConnection()->commit();
            // response data
            $response = [
                'id' => $user->getId(),
                'username' => $user->getUsername()
            ];

            return $this->setResponse($response, Response::HTTP_CREATED);
        } catch (Exception $e) {
            $this->logAPIError($e);

            $em->getConnection()->rollBack();

            return $this->setResponse(Utils::INTERNAL_SERVER_ERROR, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get user.
     *
     * Please use this service to return user.
     *
     * @OA\Parameter(name="id", in="path", required=true, description="User profile ID", @OA\Schema(type="integer"))
     *
     * @OA\Response(response=200, description="Returns profile details", @Model(type=Users::class))
     * @OA\Response(response=400, description="Bad Request", @OA\JsonContent(type="object", @OA\Property(property="errors", type="array", @OA\Items(type="object", @OA\Property(property="id", type="string", example="Bad Request", description="Bad Request")))))
     * @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Unauthorized", description="Unauthorized")))
     * @OA\Response(response=403, description="Forbidden", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Forbidden", description="Forbidden")))
     * @OA\Response(response=404, description="Not found", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Not found", description="Not found")))
     * @OA\Response(response=409, description="Conflict", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Conflict", description="Conflict")))
     * @OA\Response(response=500, description="Internal Server Error", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Internal Server Error", description="Internal Server Error")))
     *
     * @OA\Tag(name="Users SECURED")
     * @Security(name="JWT")
     *
     * @Get("/secured/users/{id}", name="get_user_secured", options={ "method_prefix" = false })
     *
     */
    final public function show(Request $request, mixed $id): JsonResponse
    {
        parent::$elementToExclude[] = 'users';
        return parent::show($request, $id);
    }

    /**
     * Update user.
     *
     * Please use this service to update user.
     *
     * @OA\Parameter(name="id", in="path", required=true, description="User ID", @OA\Schema(type="integer"))
     * @OA\RequestBody(
     *     description="User data",
     *     required=true,
     *     @OA\JsonContent(
     *          required={"fullName"},
     *          @OA\Property(property="fullName", type="string", example="Mike Smith", description="First name"),
     *          @OA\Property(property="phoneNumber", type="string", example="13334444333", description="Phone number"),
     *     )
     * )
     *
     * @OA\Response(response=200, description="Returns profile details", @Model(type=Users::class))
     * @OA\Response(response=400, description="Bad Request", @OA\JsonContent(type="object", @OA\Property(property="errors", type="array", @OA\Items(type="object", @OA\Property(property="id", type="string", example="Bad Request", description="Bad Request")))))
     * @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Unauthorized", description="Unauthorized")))
     * @OA\Response(response=403, description="Forbidden", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Forbidden", description="Forbidden")))
     * @OA\Response(response=404, description="Not found", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Not found", description="Not found")))
     * @OA\Response(response=409, description="Conflict", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Conflict", description="Conflict")))
     * @OA\Response(response=500, description="Internal Server Error", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Internal Server Error", description="Internal Server Error")))
     *
     * @OA\Tag(name="Users SECURED")
     * @Security(name="JWT")
     *
     * @Put("/secured/users/{id}", name="update_user_secured", options={ "method_prefix" = false })
     *
     */
    final public function update(Request $request, mixed $id): JsonResponse
    {

        $em = $this->doctrine->getManager();
        $em->getConnection()->beginTransaction();

        try {

            // validate request parameters
            $errors = $this->validationManager->validate('updateUserProfile');
            if (!empty($errors)) {
                return $this->setResponse($errors, Response::HTTP_BAD_REQUEST);
            }

            // check if user profile exist
            $userProfile = $em->getRepository(UsersProfiles::class)->findOneBy(['id' => $id]);
            if (!$userProfile) {
                return $this->setResponse(Utils::USER_PROFILE_NOT_FOUND, Response::HTTP_BAD_REQUEST);
            }

            if ($userProfile->getUsers() !== $this->detectUser() && !(in_array(UsersRoles::SUPER_ADMIN, $this->detectUser()?->getRoles(), true) || in_array(UsersRoles::ADMIN, $this->detectUser()?->getRoles(), true))) {
                return $this->setResponse(Utils::NOT_AUTHORIZED, Response::HTTP_FORBIDDEN);
            }


            $updateBearerToken = false;
            if ($request->get('email') !== $userProfile->getUsers()->getEmail()) {
                $existEmail = $em->getRepository(Users::class)->findOneBy(['email' => $request->get('email')]);
                if ($existEmail) {
                    return $this->setResponse(['email' => Utils::EMAIL_EXISTS], Response::HTTP_BAD_REQUEST);
                }
                $userProfile->getUsers()->setEmail($request->get('email'));
                $userProfile->getUsers()->setUsername($request->get('email'));
                $em->persist($userProfile->getUsers());

                $updateBearerToken = true;
            }

            $userProfile->setFullName(trim($request->get('fullName')));
            $userProfile->setPhoneNumber(trim($request->get('phoneNumber')));

            $em->persist($userProfile);
            $em->flush();

            // commit transaction
            $em->getConnection()->commit();

            $newBearerToken = null;
            if ($updateBearerToken) {
                $newBearerToken = $this->jwtManager->create(
                    $userProfile->getUsers()
                );
            }

            $response = ArrayHelper::objectToArrayRecursive($userProfile, ['users']);
            $response['bearerToken'] = $newBearerToken;

            // return response
            return $this->setResponse($response);
        } catch (Exception $e) {
            $this->logAPIError($e);

            $em->getConnection()->rollBack();

            return $this->setResponse(Utils::INTERNAL_SERVER_ERROR, Response::HTTP_INTERNAL_SERVER_ERROR);
        }

    }

    /**
     * Delete user.
     *
     * Please use this service to delete user.
     *
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer"))
     *
     * @OA\Response(response=200, description="Returns list", @OA\JsonContent(type="array", @OA\Items(type="object", ref=@Model(type=Users::class))))
     * @OA\Response(response=201, description="Returns profile details", @Model(type=Users::class))
     * @OA\Response(response=204, description="No Content")
     * @OA\Response(response=400, description="Bad Request", @OA\JsonContent(type="object", @OA\Property(property="errors", type="array", @OA\Items(type="object", @OA\Property(property="id", type="string", example="Bad Request", description="Bad Request")))))
     * @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Unauthorized", description="Unauthorized")))
     * @OA\Response(response=403, description="Forbidden", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Forbidden", description="Forbidden")))
     * @OA\Response(response=404, description="Not found", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Not found", description="Not found")))
     * @OA\Response(response=409, description="Conflict", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Conflict", description="Conflict")))
     * @OA\Response(response=500, description="Internal Server Error", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Internal Server Error", description="Internal Server Error")))
     *
     * @OA\Tag(name="Users SECURED")
     * @Security(name="JWT")
     *
     * @Delete("/secured/users/{id}", name="delete_user_secured", options={ "method_prefix" = false })
     *
     */
    final public function delete(Request $request, mixed $id): JsonResponse
    {

        $em = $this->doctrine->getManager();
        $em->getConnection()->beginTransaction();

        try {

            // validate request parameters
            $errors = $this->validationManager->validate('deleteUserProfile');
            if (!empty($errors)) {
                return $this->setResponse($errors, Response::HTTP_BAD_REQUEST);
            }

            // check if user profile exist
            $userProfile = $em->getRepository(UsersProfiles::class)->findOneBy(['id' => $id]);
            if (!$userProfile) {
                return $this->setResponse(Utils::USER_PROFILE_NOT_FOUND, Response::HTTP_BAD_REQUEST);
            }

            $userProfile->setStatus(Status::DELETED);
            $user = $userProfile->getUsers();
            if ($user) {
                $user->setEmail($userProfile->getId() . '-' . $user->getEmail());
                $user->setUsername($userProfile->getId() . '-' . $user->getUsername());
            }

            $em->persist($userProfile);
            $em->flush();

            // commit transaction
            $em->getConnection()->commit();

            // return response
            return $this->setResponse($userProfile);
        } catch (Exception $e) {
            $this->logAPIError($e);

            $em->getConnection()->rollBack();

            return $this->setResponse(Utils::INTERNAL_SERVER_ERROR, Response::HTTP_INTERNAL_SERVER_ERROR);
        }

    }

}
