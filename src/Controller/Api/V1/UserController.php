<?php

namespace App\Controller\Api\V1;

use ApiPlatform\OpenApi\Model\RequestBody;
use App\DTO\UserDTO;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializerBuilder;
use Lexik\Bundle\JWTAuthenticationBundle\Response\JWTAuthenticationFailureResponse;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations\Items;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Test\Constraint\ResponseIsSuccessful;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use OpenApi\Attributes as OA;

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
        return new ResponseIsSuccessful();
    }

    // schema: new OA\Schema(
    //     properties: ["username", "password"],
    //     type: "json"
    // ),
    //
    #[Route(path: "/register", name: "api_v1_register", methods: ["POST"])]
    #[
        OA\Post(
            path: "/api/v1/register",
            summary: "User Authentication",
            description: "Authentication and get token",
            requestBody: new OA\RequestBody(
                required: true,
                content: [
                    "apllication\json" => new OA\JsonContent(
                        properties: [
                            new OA\Property(
                                property: "email",
                                type: "string",
                                description: "username (user email)",
                                example: "user@example.com"
                            ),
                            new OA\Property(
                                property: "password",
                                type: "string",
                                description: "password (user password)",
                                example: "password"
                            ),
                        ]
                    ),
                ]
            ),
            responses: [
                new OA\Response(
                    description: "success registration",
                    response: 201,
                    content: new OA\JsonContent(
                        properties: [
                            new OA\Property(property: "token", type: "string"),
                        ]
                    )
                ),
                new OA\Response(
                    description: "invalid credentials",
                    response: 400,
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
        JWTTokenManagerInterface $JWTManager
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
                    "message" => "Invalid credantials",
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

        $user = User::fromDTO($userDto);

        $user->setPassword(
            $this->passwordHasher->hashPassword($user, $user->getPassword())
        );

        $user->setRoles(["ROLE_USER"]);

        $entityManager->persist($user);

        $entityManager->flush();

        return new JsonResponse(
            [
                "token" => $JWTManager->create($user),
            ],
            201
        );
    }

    #[Route(path: "/users/current", methods: ["GET"])]
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
    #[Security(name: "Bearer")]
    #[OA\Tag(name: "user")]
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
