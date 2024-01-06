<?php

namespace App\Controller\Secured;

use App\Controller\ApiController;
use App\Entity\Books;
use App\Utils\Module\Security\SecurityManager;
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
class BooksController extends ApiController
{
    public function __construct(Logger $logger, JWTManager $jwtManager, RefreshTokenManager $refreshTokenManager, RefreshTokenGeneratorInterface $refreshTokenGenerator, ValidationManager $validationManager, SecurityManager $securityManager, ManagerRegistry $doctrine, UserPasswordHasherInterface $passwordHasher)
    {
        parent::__construct($logger, $jwtManager, $refreshTokenManager, $refreshTokenGenerator, $validationManager, $securityManager, $doctrine, $passwordHasher);
        parent::$model = Books::class;
    }

    /**
     * Get books.
     *
     * Please use this service to get books.
     *
     * @OA\Parameter(name="status", in="query", example="1", description="Status comma-separed", @OA\Schema(type="string"))
     * @OA\Parameter(name="q", in="query", description="Search term", @OA\Schema(type="string"))
     * @OA\Parameter(name="fields", in="query", description="Fields to return, comma separated values: field1,field2", @OA\Schema(type="string"))
     * @OA\Parameter(name="sort", in="query", example="id", description="Sort options: fieldName or -fieldName", @OA\Schema(type="string"))
     * @OA\Parameter(name="offset", in="query", example=1, description="Offset for pagination", @OA\Schema(type="integer"))
     * @OA\Parameter(name="limit", in="query", example=20, description="Limit for pagination", @OA\Schema(type="integer"))
     *
     * @OA\Response(response=200, description="Returns profile details", @Model(type=Books::class))
     * @OA\Response(response=400, description="Bad Request", @OA\JsonContent(type="object", @OA\Property(property="errors", type="array", @OA\Items(type="object", @OA\Property(property="id", type="string", example="Bad Request", description="Bad Request")))))
     * @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Unauthorized", description="Unauthorized")))
     * @OA\Response(response=403, description="Forbidden", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Forbidden", description="Forbidden")))
     * @OA\Response(response=404, description="Not found", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Not found", description="Not found")))
     * @OA\Response(response=409, description="Conflict", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Conflict", description="Conflict")))
     * @OA\Response(response=500, description="Internal Server Error", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Internal Server Error", description="Internal Server Error")))
     *
     * @OA\Tag(name="Books SECURED")
     * @Security(name="JWT")
     *
     * @Get("/secured/books", name="get_books_secured", options={ "method_prefix" = false })
     *
     */
    final public function index(Request $request): JsonResponse
    {
        return parent::index($request);
    }

    /**
     * Create book.
     *
     * Please use this service to create a book.
     *
     * @OA\RequestBody(
     *     description="Book data",
     *     required=true,
     *     @OA\JsonContent(
     *          @OA\Property(property="status", type="integer", example=1, description="Status map: ACTIVE - 1, INACTIVE - 0"),
     *          @OA\Property(property="title", type="string", example="Book name", description="Book title"),
     *          @OA\Property(property="description", type="string", example="Book are about....", description="Book description"),
     *          @OA\Property(property="author", type="string", example="William", description="Book author"),
     *          @OA\Property(property="price", type="float", example=123.12, description="Book price"),
     *     )
     * )
     * @OA\Response(
     *     response=201, description="Returns book data",
     *     @OA\JsonContent(
     *         type="object",
     *         @OA\Property(property="id", type="integer", example=1, description="Book unique identifier"),
     *         @OA\Property(property="status", type="integer", example=1, description="Status map: ACTIVE - 1, INACTIVE - 0"),
     *         @OA\Property(property="title", type="string", example="Book name", description="Book title"),
     *         @OA\Property(property="description", type="string", example="Book are about....", description="Book description"),
     *         @OA\Property(property="author", type="string", example="William", description="Book author"),
     *         @OA\Property(property="price", type="float", example=123.12, description="Book price"),
     *         @OA\Property(property="dateUpdated", type="string", example="2023-11-19 14:45:53", description="Book updated time"),
     *         @OA\Property(property="dateCreated", type="string", example="2023-11-19 14:45:53", description="Book created time"),
     *    )
     * )
     *
     * @OA\Response(response=400, description="Bad Request", @OA\JsonContent(type="object", @OA\Property(property="errors", type="array", @OA\Items(type="object", @OA\Property(property="id", type="string", example="Bad Request", description="Bad Request")))))
     * @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Unauthorized", description="Unauthorized")))
     * @OA\Response(response=500, description="Internal Server Error", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Internal Server Error", description="Internal Server Error")))
     *
     * @OA\Tag(name="Books SECURED")
     * @Security(name="JWT")
     *
     * @Post("/secured/books", name="create_book_secured", options={ "method_prefix" = false })
     *
     */
    final public function create(Request $request): JsonResponse
    {
        return parent::create($request);
    }

    /**
     * Get book.
     *
     * Please use this service to return book.
     *
     * @OA\Parameter(name="id", in="path", required=true, description="Books ID", @OA\Schema(type="integer"))
     *
     * @OA\Response(
     *     response=200, description="Returns book details",
     *     @OA\JsonContent(
     *         type="object",
     *          @OA\Property(property="id", type="integer", example=1, description="Book unique identifier"),
     *          @OA\Property(property="status", type="integer", example=1, description="Status map: ACTIVE - 1, INACTIVE - 0"),
     *          @OA\Property(property="title", type="string", example="Book name", description="Book title"),
     *          @OA\Property(property="description", type="string", example="Book are about....", description="Book description"),
     *          @OA\Property(property="author", type="string", example="William", description="Book author"),
     *          @OA\Property(property="price", type="float", example=123.12, description="Book price"),
     *          @OA\Property(property="dateUpdated", type="string", example="2023-11-19 14:45:53", description="Book updated time"),
     *          @OA\Property(property="dateCreated", type="string", example="2023-11-19 14:45:53", description="Book created time"),
     *    )
     * )
     * @OA\Response(response=400, description="Bad Request", @OA\JsonContent(type="object", @OA\Property(property="errors", type="array", @OA\Items(type="object", @OA\Property(property="id", type="string", example="Bad Request", description="Bad Request")))))
     * @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Unauthorized", description="Unauthorized")))
     * @OA\Response(response=404, description="Not found", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Not found", description="Not found")))
     * @OA\Response(response=500, description="Internal Server Error", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Internal Server Error", description="Internal Server Error")))
     *
     * @OA\Tag(name="Books SECURED")
     * @Security(name="JWT")
     *
     * @Get("/secured/books/{id}", name="get_book_secured", options={ "method_prefix" = false })
     *
     */
    final public function show(Request $request, mixed $id): JsonResponse
    {
        return parent::show($request, $id);
    }

    /**
     * Update book.
     *
     * Please use this service to update book.
     *
     * @OA\Parameter(name="id", in="path", required=true, description="Book ID", @OA\Schema(type="integer"))
     * @OA\RequestBody(
     *     description="Book data",
     *     required=true,
     *     @OA\JsonContent(
     *           @OA\Property(property="status", type="integer", example=1, description="Status map: ACTIVE - 1, INACTIVE - 0"),
     *           @OA\Property(property="title", type="string", example="Book name", description="Book title"),
     *           @OA\Property(property="description", type="string", example="Book are about....", description="Book description"),
     *           @OA\Property(property="author", type="string", example="William", description="Book author"),
     *           @OA\Property(property="price", type="float", example=123.12, description="Book price"),
     *     )
     * )
     *
     * @OA\Response(
     *     response=200, description="Returns book details",
     *     @OA\JsonContent(
     *         type="object",
     *           @OA\Property(property="id", type="integer", example=1, description="Book unique identifier"),
     *           @OA\Property(property="status", type="integer", example=1, description="Status map: ACTIVE - 1, INACTIVE - 0"),
     *           @OA\Property(property="title", type="string", example="Book name", description="Book title"),
     *           @OA\Property(property="description", type="string", example="Book are about....", description="Book description"),
     *           @OA\Property(property="author", type="string", example="William", description="Book author"),
     *           @OA\Property(property="price", type="float", example=123.12, description="Book price"),
     *           @OA\Property(property="dateUpdated", type="string", example="2023-11-19 14:45:53", description="Book updated time"),
     *           @OA\Property(property="dateCreated", type="string", example="2023-11-19 14:45:53", description="Book created time"),
     *    )
     * )
     * @OA\Response(response=400, description="Bad Request", @OA\JsonContent(type="object", @OA\Property(property="errors", type="array", @OA\Items(type="object", @OA\Property(property="id", type="string", example="Bad Request", description="Bad Request")))))
     * @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Unauthorized", description="Unauthorized")))
     * @OA\Response(response=404, description="Not found", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Not found", description="Not found")))
     * @OA\Response(response=500, description="Internal Server Error", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Internal Server Error", description="Internal Server Error")))
     *
     * @OA\Tag(name="Books SECURED")
     * @Security(name="JWT")
     *
     * @Put("/secured/books/{id}", name="update_book_secured", options={ "method_prefix" = false })
     *
     */
    final public function update(Request $request, mixed $id): JsonResponse
    {
        return parent::update($request, $id);
    }

    /**
     * Delete book.
     *
     * Please use this service to delete book.
     *
     * @OA\Parameter(name="id", in="path", required=true, description="Book ID", @OA\Schema(type="integer"))
     *
     * @OA\Response(response=204, description="No Content")
     * @OA\Response(response=400, description="Bad Request", @OA\JsonContent(type="object", @OA\Property(property="errors", type="array", @OA\Items(type="object", @OA\Property(property="id", type="string", example="Bad Request", description="Bad Request")))))
     * @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Unauthorized", description="Unauthorized")))
     * @OA\Response(response=404, description="Not found", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Not found", description="Not found")))
     * @OA\Response(response=500, description="Internal Server Error", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Internal Server Error", description="Internal Server Error")))
     *
     * @OA\Tag(name="Books SECURED")
     * @Security(name="JWT")
     *
     * @Delete("/secured/books/{id}", name="delete_book_secured", options={ "method_prefix" = false })
     *
     */
    final public function delete(Request $request, mixed $id): JsonResponse
    {
        return parent::delete($request, $id);
    }

}