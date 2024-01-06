<?php

namespace App\Controller;

use App\Entity\RefreshToken;
use App\Entity\Users;
use App\Utils\Constants\AuthCookies;
use App\Utils\Constants\Status;
use App\Utils\Constants\Utils;
use DateTime;
use Doctrine\Persistence\ObjectManager;
use Exception;
use FOS\RestBundle\Controller\Annotations\Post;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenInterface;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class JwtTokenController extends ApiController
{

    /**
     * HttpOnly cookie authentication service.
     *
     * Please use this service to generate a httponly cookie for a user.
     *
     * @OA\RequestBody(
     *     description="User credentials for authentication",
     *     @OA\JsonContent(
     *          required={"username", "accessToken"},
     *          @OA\Property(property="username", type="string", example="mike.smith@domain.com", description="Username"),
     *          @OA\Property(property="accessToken", type="string", example="0b407a9e-a130-44e9-8788-2352e8be5145", description="User access token")
     *     )
     * )
     * @OA\Response(
     *     response=200,
     *     description="Returns user credentials with access token",
     *     @OA\JsonContent(
     *         type="object",
     *         @OA\Property(property="refreshToken", type="string", example="8f9033a92ce7cbc63413e79846..", description="JWT token that will be refreshed to keep current session alive"),
     *         @OA\Property(property="bearerToken", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJpYXQiOjE2MTg0MjU4NTIsImV4cCI6MTY0OTUyOTg..", description="Bearer token")
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
     * @OA\Tag(name="JWT Token")
     *
     * @Post("/token", name="generate_jwt_token_public", options={ "method_prefix" = false })
     *
     */
    final public function generateToken(Request $request): JsonResponse|Response
    {
        $em = $this->doctrine->getManager();

        try {
            // validate request parameters
            $errors = $this->validationManager->validate('generateToken');
            if (!empty($errors)) {
                return $this->setResponse($errors, Response::HTTP_BAD_REQUEST);
            }

            // check user by username
            $user = $em->getRepository(Users::class)->findOneBy(['username' => $request->get('username'), 'accessToken' => $request->get('accessToken'), 'status' => Status::ACTIVE]);
            if (!$user) {
                return $this->setResponse(Utils::USER_NOT_FOUND, Response::HTTP_NOT_FOUND);
            }

            // Create new refresh token
            $refreshToken = $this->refreshTokenGenerator->createForUserWithTtl($user, $this->getParameter('project')['refresh_token_ttl']);

            $refreshToken->setUsername($user->getUsername());
            $this->updateRefreshToken($refreshToken, $em, $request);

            // Create new JWT
            $token = $this->jwtManager->create($user);

            $response = new Response();
            $response->headers->set('Content-Type', 'application/json');

            $response->setContent(json_encode(['refreshToken' => $refreshToken->getRefreshToken(), 'bearerToken' => $token]));

            // set authentication http-only cookie
            $this->attachHeaderCookie($response, AuthCookies::BEARER, $token, $request->isSecure(), $user);

            return $response;
        } catch (Exception $e) {
            $this->logAPIError($e);

            return $this->setResponse(Utils::INTERNAL_SERVER_ERROR, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @param RefreshTokenInterface $refreshToken
     * @param ObjectManager $em
     * @param Request $request
     * @return void
     */
    private function updateRefreshToken(RefreshTokenInterface $refreshToken, ObjectManager $em, Request $request): void
    {

        // Set the validation date depending on the configuration
        $datetime = new DateTime();
        $datetime->modify('+' . $this->getParameter('project')['token_ttl'] . ' seconds');

        while (count($em->getRepository(RefreshToken::class)->findBy(['refreshToken' => $refreshToken->getRefreshToken()])) > 0) {
            $refreshToken->setRefreshToken();
        }

        $refreshToken->setValid($datetime);
        $deleteRefreshTokens = $em->getRepository(RefreshToken::class)->findBy(['username' => $request->get('username')]);
        if (count($deleteRefreshTokens)) {
            foreach ($deleteRefreshTokens as $deleteRefreshToken) {
                $em->remove($deleteRefreshToken);
                $em->flush();
            }
        }

        $this->refreshTokenManager->save($refreshToken);
    }

    /**
     * HttpOnly cookie refresh service.
     *
     * Please use this service to refresh a httponly cookie for a user.
     *
     * @OA\RequestBody(
     *     description="User credentials for authentication",
     *     @OA\JsonContent(
     *          required={"token"},
     *          @OA\Property(property="token", type="string", example="previous-token-example", description="User's previous token")
     *     )
     * )
     * @OA\Response(
     *     response=200,
     *     description="Returns user new refresh token",
     *     @OA\JsonContent(
     *         type="object",
     *         @OA\Property(property="token", type="string", example="JWT token that will be refreshed to keep current session alive", description="User token")
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
     * @OA\Tag(name="JWT Token")
     * @Security(name="JWT")
     *
     * @Post("/secured/token/refresh", name="refresh_jwt_token_secured", options={ "method_prefix" = false })
     *
     */
    final public function refreshToken(Request $request): JsonResponse|Response
    {

        $em = $this->doctrine->getManager();

        try {

            $refreshToken = $this->refreshTokenManager->getLastFromUsername($this->securityManager->requestUser->getUsername());
            if (!$refreshToken || !($refreshToken->getRefreshToken()) || $refreshToken->getRefreshToken() !== $request->get('token')) {
                return $this->setResponse(Utils::TOKEN_MISMATCH, Response::HTTP_BAD_REQUEST);
            }

            $this->updateRefreshToken($refreshToken, $em, $request);

            // Create new JWT
            $token = $this->jwtManager->create($this->securityManager->requestUser);

            $response = new Response();
            $response->headers->set('Content-Type', 'application/json');

            // set return value: the new refresh token
            $response->setContent(json_encode(['refreshToken' => $refreshToken->getRefreshToken(), 'bearerToken' => $token], JSON_THROW_ON_ERROR));

            // set authentication http-only cookie
            $this->attachHeaderCookie($response, AuthCookies::BEARER, $token, $request->isSecure());

            return $response;
        } catch (Exception $e) {
            $this->logAPIError($e);

            return $this->setResponse(Utils::INTERNAL_SERVER_ERROR, Response::HTTP_INTERNAL_SERVER_ERROR);
        }

    }

}
