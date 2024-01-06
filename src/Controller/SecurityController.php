<?php

namespace App\Controller;

use App\Entity\PasswordsHistory;
use App\Entity\Users;
use App\Entity\UsersProfiles;
use App\Utils\Constants\Status;
use App\Utils\Constants\Users\UsersStatus;
use App\Utils\Constants\Utils;
use App\Utils\Helpers\AuthenticationHelper;
use App\Utils\Helpers\StringHelper;
use App\Utils\Module\Users\UsersManager;
use DateTime;
use Exception;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Put;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityController extends ApiController
{

    /**
     * Register service.
     *
     * Please use this service to create a user from register screen.
     *
     * @OA\RequestBody(
     *     description="User data",
     *     required=true,
     *     @OA\JsonContent(
     *          required={"email", "firstName", "lastName", "phoneNumber", "password", "confirmPassword", "agreeWithTerms"},
     *          @OA\Property(property="email", type="string", example="mike.smith@domain.com", description="User email"),
     *          @OA\Property(property="fullName", type="string", example="Mike Smith", description="Full name"),
     *          @OA\Property(property="phoneNumber", type="string", example="+1333444333", description="User phone number"),
     *          @OA\Property(property="password", type="string", example="MyPass123@", description="User's password"),
     *          @OA\Property(property="confirmPassword", type="string", example="MyPass123@", description="Confirm Password field"),
     *          @OA\Property(property="gReCaptchaResponse", type="string", example="test", description="Google ReCaptcha Response"),
     *          @OA\Property(property="agreeWithTerms", type="boolean", example=true, description="Agree with Terms")
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
     * @OA\Tag(name="Security PUBLIC")
     *
     * @Post("/register", name="register_public", options={ "method_prefix" = false })
     *
     */
    final public function register(Request $request): JsonResponse
    {

        $em = $this->doctrine->getManager();
        $em->getConnection()->beginTransaction();

        try {

            // validate request parameters
            $errors = $this->validationManager->validate('createUser');
            if (!empty($errors)) {
                return $this->setResponse($errors, Response::HTTP_BAD_REQUEST);
            }

            // check if the passwords match and (re)write error message
            if (strcmp($request->get('password'), $request->get('confirmPassword')) !== 0) {
                return $this->setResponse(['confirmPassword' => Utils::PASSWORD_NOT_MATCH], Response::HTTP_CONFLICT);
            }

            $params = $request->request->all();

            $user = $em->getRepository(Users::class)->findOneBy(['username' => $params['email']]);
            if ($user) {
                return $this->setResponse(Utils::USER_EXISTS, Response::HTTP_CONFLICT);
            }

            $userManager = new UsersManager($em, $request, $this->passwordHasher);
            $user = $userManager->createUser();
            $userProfile = $userManager->createUserProfile($user);

            $request->request->set('username', $user->getUsername());
            $auth = json_decode($this->authentication($request)->getContent(), true, 512, JSON_THROW_ON_ERROR);

            $request->request->set('accessToken', $auth['accessToken']);
            $token = json_decode($this->forward('App\Controller\JwtTokenController::generateToken', ['request' => $request])->getContent(), true);

            // commit transaction
            $em->getConnection()->commit();

            // response data
            $response = [
                'id' => $user->getId(),
                'username' => $user->getUsername(),
                'token' => $token
            ];

            return $this->setResponse($response, Response::HTTP_CREATED);
        } catch (Exception $e) {
            $this->logAPIError($e);

            $em->getConnection()->rollBack();

            return $this->setResponse(Utils::INTERNAL_SERVER_ERROR, Response::HTTP_INTERNAL_SERVER_ERROR);
        }

    }
    
    /**
     * Basic authentication service.
     *
     * Please use this service to authenticate users.
     *
     * @OA\RequestBody(
     *     description="User credentials for authentication",
     *     @OA\JsonContent(
     *          required={"username"},
     *          @OA\Property(property="username", type="string", example="mike.smith@domain.com", description="Username"),
     *          @OA\Property(property="password", type="string", example="MyPass123@", description="User's password"),
     *          @OA\Property(property="gReCaptchaResponse", type="string", example="test", description="Google ReCaptcha Response")
     *     )
     * )
     * @OA\Response(
     *     response=200,
     *     description="Returns user credentials with access token",
     *     @OA\JsonContent(
     *         type="object",
     *         @OA\Property(property="id", type="integer", example=1, description="User unique identifier"),
     *         @OA\Property(property="username", type="string", example="mike.smith@domain.com", description="Username"),
     *         @OA\Property(property="accessToken", type="string", example="oLtr3gf43vj3a5mX3pSa4IhWWXlhyB5A", description="User access token for future API secured calls")
     *     )
     * )
     *
     * @OA\Response(response=400, description="Bad Request", @OA\JsonContent(type="object", @OA\Property(property="errors", type="array", @OA\Items(type="object", @OA\Property(property="id", type="string", example="Bad Request", description="Bad Request")))))
     * @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Unauthorized", description="Unauthorized")))
     * @OA\Response(response=403, description="Forbidden", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Forbidden", description="Forbidden")))
     * @OA\Response(response=404, description="Not found", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Not found", description="Not found")))
     * @OA\Response(response=409, description="Conflict", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Conflict", description="Conflict")))
     * @OA\Response(response=500, description="Internal Server Error", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Internal Server Error", description="Internal Server Error")))
     *
     * @OA\Tag(name="Security PUBLIC")
     *
     * @Post("/authentication", name="authentication_public", options={ "method_prefix" = false })
     *
     */
    final public function authentication(Request $request): JsonResponse
    {
        $em = $this->doctrine->getManager();

//        try {
            // set a random sleep time (between 0 and 2 seconds) so hackers won't know what's the site built upon so afterwards they'd try specific vulnerabilities
            usleep(random_int(200000, 2000000));

            // validate request parameters
            $errors = $this->validationManager->validate('authentication');
            if (!empty($errors)) {
                return $this->setResponse($errors, Response::HTTP_BAD_REQUEST);
            }

            // checking for 5 failed login attempts
            $authStatus = AuthenticationHelper::checkAttempts($request->get('username'), $em);
            if ($authStatus['locked']) {
                $errors['email'] = Utils::ACCOUNT_LOCKED;
                return $this->setResponse($errors, Response::HTTP_NOT_FOUND);
            }

            $user = $em->getRepository(Users::class)->findOneBy(['username' => $request->get('username')]);
            if (!$user || ($user->getStatus() !== Status::ACTIVE)) {
                return $this->setResponse(Utils::USER_NOT_FOUND, Response::HTTP_NOT_FOUND);
            }

            $userProfile = $em->getRepository(UsersProfiles::class)->findOneBy(['users' => $user->getId()]);
            if (!$userProfile) {
                return $this->setResponse(Utils::USER_PROFILE_NOT_FOUND, Response::HTTP_BAD_REQUEST);
            }

            // check password
            if (!$this->passwordHasher->isPasswordValid($user, $request->get('password'))) {
                AuthenticationHelper::wrongAttempt($request->get('username'), $em);
                $errors['email'] = Utils::PASSWORD_NOT_MATCH;
                return $this->setResponse($errors, Response::HTTP_FORBIDDEN);
            }

            // successfully authentication, generate and update access token, update last login date
            $accessToken = StringHelper::generateUuid();
            $user->setAccessToken($accessToken);
            $user->setAuthenticationDate(new DateTime('UTC'));
            $em->persist($user);
            $em->flush();

            $response = [];
            $response['id'] = $user->getId();
            $response['userProfileId'] = $userProfile->getId();
            $response['username'] = $user->getUsername();
            $response['accessToken'] = $accessToken;

            // return response in json
            return $this->setResponse($response);
//        } catch (Exception $e) {
//            $this->logAPIError($e);
//
//            return $this->setResponse(Utils::INTERNAL_SERVER_ERROR, Response::HTTP_INTERNAL_SERVER_ERROR);
//        }
    }

    /**
     * Set password service.
     *
     * Please use this service to set the password.
     *
     * @OA\RequestBody(
     *     description="User email for authentication",
     *     @OA\JsonContent(
     *          required={"id", "timestamp", "token", "password", "confirmPassword"},
     *          @OA\Property(property="id", type="integer", example=1, description="User id"),
     *          @OA\Property(property="password", type="string", example="MyPass123@", description="User password"),
     *          @OA\Property(property="confirmPassword", type="string", example="MyPass123@", description="User confirm password")
     *     )
     * )
     * @OA\Response(
     *     response=200, description="Returns user credentials",
     *     @OA\JsonContent(
     *         type="object",
     *         @OA\Property(property="id", type="integer", example=1, description="User unique identifier"),
     *         @OA\Property(property="username", type="string", example="mike.smith@domain.com", description="Username for authenticated user")
     *     )
     * )
     *
     * @OA\Response(response=400, description="Bad Request", @OA\JsonContent(type="object", @OA\Property(property="errors", type="array", @OA\Items(type="object", @OA\Property(property="id", type="string", example="Bad Request", description="Bad Request")))))
     * @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Unauthorized", description="Unauthorized")))
     * @OA\Response(response=403, description="Forbidden", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Forbidden", description="Forbidden")))
     * @OA\Response(response=404, description="Not found", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Not found", description="Not found")))
     * @OA\Response(response=409, description="Conflict", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Conflict", description="Conflict")))
     * @OA\Response(response=500, description="Internal Server Error", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Internal Server Error", description="Internal Server Error")))
     *
     * @OA\Tag(name="Security SECURED")
     *
     * @Post("/secured/users/password/set", name="set_profile_password", options={ "method_prefix" = false })
     *
     */
    final public function setPassword(Request $request): JsonResponse
    {
        $em = $this->doctrine->getManager();

        try {
            // validate request parameters
            $errors = $this->validationManager->validate('setPassword');

            if (!empty($errors)) {
                return $this->setResponse($errors, Response::HTTP_BAD_REQUEST);
            }

            // check if the passwords match and (re)write error message
            if ($request->get('confirmPassword') && strcmp($request->get('password'), $request->get('confirmPassword')) !== 0) {
                $errors['confirmPassword'] = Utils::PASSWORD_NOT_MATCH;
                return $this->setResponse($errors, Response::HTTP_BAD_REQUEST);
            }

            // check user
            $user = $em->getRepository(Users::class)->findOneBy(['id' => $request->get('id')]);
            if (!$user) {
                return $this->setResponse(Utils::USER_NOT_FOUND, Response::HTTP_NOT_FOUND);
            }

            if($user->getId() !== $this->securityManager->requestUser->getId()) {
                return $this->setResponse(Utils::USER_NOT_FOUND, Response::HTTP_NOT_FOUND);
            }

            // keep track of the last 5 passwords and do not allow those to be reused until they become the 6th password back.
            $last5Passwords = $em->getRepository(PasswordsHistory::class)->findBy(['users' => $user->getId()], ['passwordDate' => 'DESC'], 5);


            // TODO: need to fix this code
            if (count($last5Passwords)) {
                $currentSalt = $user->getSalt();

                foreach ($last5Passwords as $last5Password) {
                    $user->setSalt($last5Password->getPasswordSalt());//temporary set salt from history
                    if (strcmp($this->passwordHasher->hashPassword($user, $request->get('password')), $last5Password->getPasswordHash()) === 0) {
                        return $this->setResponse(Utils::PASSWORD_USED_BEFORE, Response::HTTP_BAD_REQUEST);
                    }
                }

                // set salt to the previous value
                $user->setSalt($currentSalt);
            }

            // set password
            $user->setSalt(StringHelper::generateRandomString(random_int(32, 43), true));
            $user->setPassword($this->passwordHasher->hashPassword($user, $request->get('password')));
            $em->persist($user);
            $em->flush();

            // save new password to history
            $newPassword = new PasswordsHistory();
            $newPassword->setUsers($user);
            $newPassword->setPasswordDate(new DateTime());
            $newPassword->setPasswordSalt($user->getSalt());
            $newPassword->setPasswordHash($user->getPassword());
            $em->persist($newPassword);
            $em->flush();

            // return response in json
            return $this->setResponse([
                'id' => $user->getId(),
                'username' => $user->getUsername()
            ]);
        } catch (Exception $e) {
            $this->logAPIError($e);

            return $this->setResponse(Utils::INTERNAL_SERVER_ERROR, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Log out service.
     *
     * Please use this service to log out users.
     *
     * @OA\Response(
     *     response=200, description="Returns user credentials",
     *     @OA\JsonContent(
     *         type="object",
     *         @OA\Property(property="id", type="integer", example=1, description="User unique identifier"),
     *         @OA\Property(property="username", type="string", example="mike.smith@domain.com", description="Username")
     *     )
     * )
     *
     * @OA\Response(response=400, description="Bad Request", @OA\JsonContent(type="object", @OA\Property(property="errors", type="array", @OA\Items(type="object", @OA\Property(property="id", type="string", example="Bad Request", description="Bad Request")))))
     * @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Unauthorized", description="Unauthorized")))
     * @OA\Response(response=403, description="Forbidden", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Forbidden", description="Forbidden")))
     * @OA\Response(response=404, description="Not found", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Not found", description="Not found")))
     * @OA\Response(response=409, description="Conflict", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Conflict", description="Conflict")))
     * @OA\Response(response=500, description="Internal Server Error", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Internal Server Error", description="Internal Server Error")))
     *
     * @OA\Tag(name="Security SECURED")
     * @Security(name="JWT")
     *
     * @Post("/secured/users/logout", name="logout_secured", options={ "method_prefix" = false })
     *
     */
    final public function logout(): JsonResponse|Response
    {

        $em = $this->doctrine->getManager();

        try {

            // request user
            $user = $this->securityManager->requestUser;

            // set access token null
            $user->setAccessToken(null);

            $em->persist($user);
            $em->flush();

            $response = new Response();
            $response->headers->set('Content-Type', 'application/json');

            $response->setContent(json_encode(['id' => $user->getId(), 'username' => $user->getUsername()], JSON_THROW_ON_ERROR));

            $response->headers->clearCookie('BEARER');

            return $response;
        } catch (Exception $e) {
            $this->logAPIError($e);

            return $this->setResponse(Utils::INTERNAL_SERVER_ERROR, Response::HTTP_INTERNAL_SERVER_ERROR);
        }

    }

    /**
     * Forgot password service.
     *
     * Please use this service to recover the password.
     *
     * @OA\RequestBody(
     *     description="User email for authentication",
     *     @OA\JsonContent(
     *          required={"email"},
     *          @OA\Property(property="email", type="string", example="mike.smith@domain.com", description="User email address")
     *     )
     * )
     * @OA\Response(
     *     response=200, description="Returns user credentials",
     *     @OA\JsonContent(
     *         type="object",
     *         @OA\Property(property="id", type="integer", example=1, description="User unique identifier"),
     *         @OA\Property(property="email", type="string", example="mike.smith@domain.com", description="Email for authenticated user")
     *     )
     * )
     *
     * @OA\Response(response=400, description="Bad Request", @OA\JsonContent(type="object", @OA\Property(property="errors", type="array", @OA\Items(type="object", @OA\Property(property="id", type="string", example="Bad Request", description="Bad Request")))))
     * @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Unauthorized", description="Unauthorized")))
     * @OA\Response(response=403, description="Forbidden", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Forbidden", description="Forbidden")))
     * @OA\Response(response=404, description="Not found", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Not found", description="Not found")))
     * @OA\Response(response=409, description="Conflict", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Conflict", description="Conflict")))
     * @OA\Response(response=500, description="Internal Server Error", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Internal Server Error", description="Internal Server Error")))
     *
     * @OA\Tag(name="Security PUBLIC")
     * @Security(name="JWT")
     *
     * @Post("/users/password/forgot", name="forgot_password_secured", options={ "method_prefix" = false })
     *
     */
    final public function forgotPassword(Request $request): JsonResponse
    {
        $em = $this->doctrine->getManager();

        try {
            // validate request parameters
            $errors = $this->validationManager->validate('forgotPassword');
            if (!empty($errors)) {
                return $this->setResponse($errors, Response::HTTP_BAD_REQUEST);
            }

            $user = $em->getRepository(Users::class)->findOneBy(['email' => trim($request->get('email'))]);
            if (!$user) {
                return $this->setResponse(Utils::USER_NOT_FOUND, Response::HTTP_NOT_FOUND);
            }

            // TODO: implement from company
            $this->emailManager->sendEmail(
                'Reset password email!',
                $request->get('email'),
                'office@creativsoft.md',
                $this->getParameter('project')['web_url'] . '?tokenResetPassword=rqejhkrqwbrjweqrvjqwerfwqhrqwerert'
            );

            // return response in json
            return $this->setResponse([
                'id' => $user->getId(),
                'username' => $user->getEmail()
            ]);
        } catch (Exception $e) {
            $this->logAPIError($e);

            return $this->setResponse(Utils::INTERNAL_SERVER_ERROR, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
