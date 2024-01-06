<?php

namespace App\Controller;

use App\Utils\Constants\Utils;
use App\Utils\Helpers\ArrayHelper;
use App\Utils\Helpers\AuthenticationHelper;
use App\Utils\Module\Security\SecurityManager;
use App\Utils\Traits\HasCrudActions;
use App\Utils\Validation\ValidationManager;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Gesdinet\JWTRefreshTokenBundle\Doctrine\RefreshTokenManager;
use Gesdinet\JWTRefreshTokenBundle\Generator\RefreshTokenGeneratorInterface;
use JsonException;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTManager;
use Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class ApiController extends AbstractController
{
    use HasCrudActions;

    protected Logger $logger;
    protected JWTManager $jwtManager;
    protected ManagerRegistry $doctrine;
    protected SecurityManager $securityManager;
    protected ValidationManager $validationManager;
    protected RefreshTokenManager $refreshTokenManager;
    protected RefreshTokenGeneratorInterface $refreshTokenGenerator;
    protected UserPasswordHasherInterface $passwordHasher;

    public static $model;
    public static array $parameters;
    public static array $elementToExclude;

    public function __construct(Logger $logger, JWTManager $jwtManager, RefreshTokenManager $refreshTokenManager, RefreshTokenGeneratorInterface $refreshTokenGenerator, ValidationManager $validationManager, SecurityManager $securityManager, ManagerRegistry $doctrine, UserPasswordHasherInterface $passwordHasher)
    {
        $this->logger = $logger;
        $this->jwtManager = $jwtManager;
        $this->refreshTokenManager = $refreshTokenManager;
        $this->refreshTokenGenerator = $refreshTokenGenerator;
        $this->validationManager = $validationManager;
        $this->securityManager = $securityManager;
        $this->doctrine = $doctrine;
        $this->passwordHasher = $passwordHasher;
        self::$elementToExclude = ['salt', 'password', 'passwordRequestedAt', 'resetPasswordToken', 'userIdentifier'];
    }

    final public function pageNotFoundAction(): JsonResponse
    {
        return $this->setResponse(Utils::ROUTE_NOT_FOUND, Response::HTTP_NOT_FOUND);
    }

    final public function setResponse(mixed $data, int $code = Response::HTTP_OK, array $headers = [], bool $rawResponse = false): JsonResponse
    {
        $successCodes = [
            Response::HTTP_OK,
            Response::HTTP_CREATED,
            Response::HTTP_NO_CONTENT
        ];

        if (!$rawResponse && !in_array($code, $successCodes, true)) {
            $response = ['error' => $data ?? null];
        } else {
            $response = $data;
        }

        if (is_object($response)) {
            try {
                $response = ArrayHelper::objectToArrayRecursive($response, ['users']);
            } catch (Exception $e) {
                $this->logAPIError($e);
                $response = [];
            }
        }

        $headers['Access-Control-Allow-Private-Network'] = 'true';

        return new JsonResponse($response, $code, $headers);
    }

    final public function setListingConfigurations(Request $request, &$page, &$noRecords, &$sortField, &$sortType): void
    {
        $page = (int)$request->get('offset') ? (int)$request->get('offset') - 1 : 0;

        $noRecords = match ($request->get('limit')) {
            "-1" => PHP_INT_MAX,
            null => 20,
            default => (int)$request->get('limit'),
        };

        $sort = $request->get('sort', '');
        $sortFields = explode('-', $sort);

        $sortField = $sortFields[1] ?? ($sortFields[0] ?: 'id');
        $sortType = isset($sortFields[1]) ? 'DESC' : ($sortFields[0] ? 'ASC' : 'DESC');
    }

    final public function setHeaderLink(Request $request, int $page, int $noRecords, int $noTotal, array $params = []): string
    {

        // get current url
        $url = $this->generateUrl($request->get('_route'), $params, UrlGeneratorInterface::ABSOLUTE_URL);

        // get last offset
        $lastOffset = ceil($noTotal / $noRecords);

        // first, last, prev and next link
        $firstLink = $url . '?offset=1&limit=' . $noRecords;
        $lastLink = $url . '?offset=' . $lastOffset . '&limit=' . $noRecords;
        $prevLink = $nextLink = null;

        if ($page + 2 <= $lastOffset) {
            $nextLink = $url . '?offset=' . ($page + 2) . '&limit=' . $noRecords;
        }
        if ($page >= 1) {
            $prevLink = $url . '?offset=' . $page . '&limit=' . $noRecords;
        }

        // header link
        $headerLink = '<' . $firstLink . '>; rel="first", <' . $lastLink . '>; rel="last"';
        if ($prevLink) {
            $headerLink .= ', <' . $prevLink . '>; rel="prev"';
        }
        if ($nextLink) {
            $headerLink .= ', <' . $nextLink . '>; rel="next"';
        }

        return $headerLink;
    }

    final public function attachHeaderCookie(mixed $response, string $name, mixed $value, bool $isSecure, UserInterface $user = null): void
    {
        $expireDate = AuthenticationHelper::getTokenLifeTime($user ?? $this->container->get('security.token_storage')->getToken()->getUser(),
            $this->getParameter('project')['token_ttl'],
            $this->getParameter('project')['token_ttl_remember_me']
        );

        $response->headers->setCookie(
            new Cookie($name, $value, $expireDate, '/', null, $isSecure)
        );
    }

    final public function detectUser(): ?UserInterface
    {
        // check if user is anonymous
        if ($this->securityManager->requestUser) {
            return $this->securityManager->requestUser;
        }

        return null;
    }


    final public function logAPIError(\Exception $exception): void
    {
        $this->logger->error($exception->getFile() . ' | ' . $exception->getLine() . ' | ' . $exception->getMessage());
    }

}
