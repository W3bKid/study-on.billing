<?php

namespace App\Controller\Api\V1;

use App\DTO\UserDTO;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializerBuilder;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route(path: "/api/v1/users")]
class UserController extends AbstractController
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    #[Route(path: "/auth", name: "api_v1_auth", methods: ["POST"])]
    public function auth(Request $request)
    {
    }

    #[Route(path: "/register", name: "api_v1_register", methods: ["POST"])]
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

        return new JsonResponse([
            "token" => $JWTManager->create($user),
        ]);
    }
}
