<?php

namespace App\Controller\Api\V1;

use App\DTO\UserDTO;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\PaymentService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Gesdinet\JWTRefreshTokenBundle\Generator\RefreshTokenGeneratorInterface;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use JMS\Serializer\SerializerBuilder;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route(path: "/api/v1")]
class UserController extends AbstractController
{
    private UserPasswordHasherInterface $passwordHasher;
    private TokenStorageInterface $tokenStorageInterface;
    private JWTTokenManagerInterface $jwtManager;

    public function __construct(
        UserPasswordHasherInterface $passwordHasher,
        TokenStorageInterface $tokenStorageInterface,
        JWTTokenManagerInterface $jwtManager
    ) {
        $this->passwordHasher = $passwordHasher;
        $this->tokenStorageInterface = $tokenStorageInterface;
        $this->jwtManager = $jwtManager;
    }

    #[Route(path: "/auth", name: "api_v1_auth", methods: ["POST"])]
    public function auth()
    {
    }

    #[Route(path: "/register", name: "api_v1_register", methods: ["POST"])]
    #[
        OA\Post(
            path: "/api/v1/register",
            description: "Authentication and get token",
            summary: "User Authentication",
            requestBody: new OA\RequestBody(
                required: true,
                content: [
                    "application\json" => new OA\JsonContent(
                        properties: [
                            new OA\Property(
                                property: "email",
                                description: "username (user email)",
                                type: "string",
                                example: "user@example.com"
                            ),
                            new OA\Property(
                                property: "password",
                                description: "password (user password)",
                                type: "string",
                                example: "password"
                            ),
                        ]
                    ),
                ]
            ),
            responses: [
                new OA\Response(
                    response: 201,
                    description: "success registration",
                    content: new OA\JsonContent(
                        properties: [
                            new OA\Property(property: "token", type: "string"),
                            new OA\Property(property: "refreshToken", type: "string")
                        ]
                    )
                ),
                new OA\Response(
                    response: 400,
                    description: "invalid credentials",
                    content: new OA\JsonContent(
                        properties: [
                            new OA\Property(
                                property: "message",
                                type: "string"
                            ),
                        ]
                    )
                ),
            ]
        )
    ]
    #[OA\Tag(name: "user")]
    public function register(
        Request $request,
        ValidatorInterface $validator,
        EntityManagerInterface $entityManager,
        UserRepository $repository,
        JWTTokenManagerInterface $JWTManager,
        RefreshTokenGeneratorInterface $refreshTokenGenerator,
        RefreshTokenManagerInterface $refreshTokenManager,
        PaymentService $paymentService
    ) {
        $serializer = SerializerBuilder::create()->build();
        $userDto = $serializer->deserialize(
            $request->getContent(),
            UserDto::class,
            "json"
        );

        $errors = $validator->validate($userDto);
        if (count($errors) > 0) {
            return new JsonResponse(
                [
                    "message" => $errors->get(0)->getMessage(),
                ],
                400
            );
        }

        $sameUser = $repository->findBy(["email" => $userDto->email]);
        if ($sameUser) {
            return new JsonResponse(
                [
                    "message" => "User with same email already exists",
                ],
                400
            );
        }

        $entityManager->getConnection()->beginTransaction();
        try {
            $user = User::fromDTO($userDto);
            $user->setPassword(
                $this->passwordHasher->hashPassword($user, $user->getPassword())
            );
            $user->setRoles(["ROLE_USER"]);
            $entityManager->persist($user);
            $entityManager->flush();
            $paymentService->deposit($user, $_ENV['INITIAL_DEPOSIT']);
            $entityManager->getConnection()->commit();
        } catch (Exception $e) {
            $entityManager->getConnection()->rollBack();
            throw $e;
        }

        $refreshToken = $refreshTokenGenerator->createForUserWithTtl(
            $user,
            (new \DateTime())->modify('+1 month')->getTimestamp()
        );
        $refreshTokenManager->save($refreshToken);

        return new JsonResponse(
            [
                "token" => $JWTManager->create($user),
                'refreshToken' => $refreshToken->getRefreshToken()
            ],
            201
        );
    }

    #[
        OA\Get(
            path: "/api/v1/users/current",
            description: "Get user email, balance and roles",
            responses: [
                new OA\Response(
                    response: 200,
                    description: "Get user email, balance and roles",
                    content: new OA\JsonContent(
                        properties: [
                            new OA\Property(
                                property: "username",
                                type: "string"
                            ),
                            new OA\Property(property: "roles", type: "string"),
                            new OA\Property(
                                property: "balance",
                                type: "integer"
                            ),
                        ]
                    )
                ),
                new OA\Response(
                    response: 400,
                    description: "JWT authentication failed",
                    content: new OA\JsonContent(
                        properties: [
                            new OA\Property(
                                property: "message",
                                type: "string",
                                example: "JWT authentication failed"
                            ),
                        ]
                    )
                ),
            ]
        )
    ]
    #[OA\Tag(name: "user")]
    #[Route(path: "/users/current", methods: ["GET"])]
    #[Security(name: "Bearer")]
    #[IsGranted("ROLE_USER")]
    public function currentUser(EntityManagerInterface $entityManager)
    {
        $decodedToken = $this->jwtManager->decode(
            $this->tokenStorageInterface->getToken()
        );

        $user = $entityManager
            ->getRepository(User::class)
            ->findOneBy(["email" => $decodedToken["username"]]);

        if (!$user) {
            return new JsonResponse(
                [
                    "message" => "JWT authentication failed",
                ],
                401
            );
        }

        return new JsonResponse(
            [
                "username" => $user->getEmail(),
                "roles" => $user->getRoles(),
                "balance" => $user->getBalance(),
            ],
            200
        );
    }
}
