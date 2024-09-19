<?php

namespace App\Controller\Api\V1;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Request\AuthRequest;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Validator\Constraints\PasswordStrength;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route(path: "/api/v1/users")]
class UserController extends AbstractController
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(
        UserPasswordHasherInterface $passwordHasher,
        private Security $security
    ) {
        $this->passwordHasher = $passwordHasher;
    }

    // #[Route(path: "/auth", methods: ["POST"])]
    // public function auth(AuthRequest $request, EntityManagerInterface $manager)
    // {
    //     $email = $request->getEmail();
    //     $password = $request->getPassword();

    //     $user = $manager
    //         ->getRepository(User::class)
    //         ->findOneBy(["email" => $email]);

    //     if (!$user) {
    //         return new JsonResponse(
    //             [
    //                 "message" => "Пользователь с данным email не существует",
    //             ],
    //             404
    //         );
    //     }

    //     $isPasswordValid = $this->passwordHasher->isPasswordValid(
    //         $user,
    //         $request->getPassword()
    //     );

    //     if ($isPasswordValid) {
    //     }
    // }

    /**
     * @OA\Post(
     *     path="/api/v1/auth",
     *     summary="User Authentication",
     *     description="User Authentication and Getting a JWT Token"
     * )
     * @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *        @OA\Property(
     *          property="username",
     *          type="string",
     *          description="username (user email)",
     *          example="user@example.com",
     *        ),
     *        @OA\Property(
     *          property="password",
     *          type="string",
     *          description="password",
     *          example="password",
     *        ),
     *     )
     *)
     * @OA\Response(
     *     response=200,
     *     description="User Authentication and Getting a JWT Token",
     *     @OA\JsonContent(
     *        @OA\Property(
     *          property="token",
     *          type="string",
     *        ),
     *     )
     * )
     * @OA\Response(
     *     response=401,
     *     description="Auth error. Invalid credentials",
     *     @OA\JsonContent(
     *        @OA\Property(
     *          property="code",
     *          type="string",
     *          example="401"
     *        ),
     *        @OA\Property(
     *          property="message",
     *          type="string",
     *          example="Invalid credentials"
     *        ),
     *     )
     * )
     * @OA\Response(
     *     response=400,
     *     description="Bad request",
     *     @OA\JsonContent(
     *        @OA\Property(
     *          property="code",
     *          type="string",
     *          example="400"
     *        ),
     *        @OA\Property(
     *          property="message",
     *          type="string",
     *          example="Bad request"
     *        ),
     *     )
     * )
     * @OA\Tag(name="User")
     */
    #[Route("/auth", name: "api_v1_auth", methods: ["POST"])]
    public function auth(Request $request): JsonResponse
    {
        return new JsonResponse(status: Response::HTTP_OK);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/token/refresh",
     *     description="Get new valid JWT token",
     *     tags={"User"},
     * @OA\RequestBody(
     *      @OA\JsonContent(
     *          type="object",
     *          @OA\Property(
     *              property="refresh_token",
     *              type="string"
     *          )
     *      )
     * ),
     * @OA\Response(
     *      response=200,
     *      description="JWT token",
     *      @OA\JsonContent(
     *          type="object",
     *          @OA\Property(
     *              property="token",
     *              type="string"
     *          ),
     *          @OA\Property(
     *              property="refresh_token",
     *              type="string"
     *          )
     *       )
     *    )
     * )
     * Managed by lexik/jwt-authentication-bundle. Used for only OA doc
     */
    // #[Route('/v1/token/refresh', name: 'api_v1_refresh_token', methods: ['POST'])]
    // public function refreshToken()
    // {
    //     return new JsonResponse(status: Response::HTTP_OK);
    //     //dd();
    //     //throw new \RuntimeException();
    // }

    #[Route("/v1/token/refresh", name: "api_refresh_token", methods: ["POST"])]
    public function refresh()
    {
    }
}
