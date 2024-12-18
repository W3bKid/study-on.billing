<?php

namespace App\Controller\Api\V1;

use App\DTO\CourseDTO;
use App\DTO\Create\CreateCourseDTO;
use App\Entity\Course;
use App\Enum\CourseType;
use App\Repository\CourseRepository;
use App\Service\PaymentService;
use App\Service\RoundPrice;
use App\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use Nelmio\ApiDocBundle\Attribute\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\InsufficientAuthenticationException;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route(path: "/api/v1/courses")]
class CourseController extends AbstractController
{
    #[OA\Get(
        path: "/api/v1/courses",
        description: "Get courses",
        responses: [
            new OA\Response(
                response: 200,
                description: "Transactions info",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "character_code",type: "string"),
                        new OA\Property(property: "price", type: "string"),
                        new OA\Property(property: "type", type: "string"),
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
    )]
    #[Route(path: '/', name: 'api_courses_get', methods: ['GET'])]
    public function list(CourseRepository $courseRepository): JsonResponse
    {
        $courses = $courseRepository->findAllOrderedByName();

        foreach ($courses as &$course) {
            $course['price'] = RoundPrice::roundPrice($course['price']);

            if ($course['type'] == CourseType::FREE->value){
                unset($course['price']);
            }

            $course['type'] = CourseType::from($course['type'])->getName();
        }

        return $this->json($courses);
    }

    #[OA\Get(
        path: "/api/v1/courses/{code}",
        description: "Get courses",
        responses: [
            new OA\Response(
                response: 200,
                description: "Transactions info",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "character_code",type: "string"),
                        new OA\Property(property: "price", type: "string"),
                        new OA\Property(property: "type", type: "string"),
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
    )]
    #[Route(path: '/{code}', name: 'api_courses_by_code', methods: ['GET'])]
    public function getByCode(string $code, CourseRepository $repository): JsonResponse
    {
        $course = $repository->findByCode($code);

        if (!$course) {
            throw new NotFoundHttpException();
        }

        $response = [
            'code' => $course->getCharacterCode(),
            'type' => $course->getType(),
            'price' => RoundPrice::roundPrice($course->getPrice()),
        ];

        return $this->json($response);
    }

    /**
     * @throws JWTDecodeFailureException
     */
    #[Route(path: '/{code}/pay', name: 'api_courses_pay', methods: ['POST'])]
    #[Security(name: "Bearer")]
    #[IsGranted("ROLE_USER")]
    public function pay(
        string $code,
        CourseRepository $repository,
        UserService $userService,
        PaymentService $paymentService
    ): JsonResponse
    {
        $course = $repository->findByCode($code);

        if (!$course) {
            throw new NotFoundHttpException();
        }

        if ($course->getType() == CourseType::FREE->value) {
            return $this->json([
                'success' => true,
                'course_type' => CourseType::FREE->getName()
            ]);
        }

        $response = [
            'success' => true,
            'course_type' => CourseType::tryFrom($course->getType())->getName(),
        ];

        try {
            $transaction = $paymentService->pay($userService->getFromStorage(), $course);

            if ($course->getType() === CourseType::RENTAL->value) {
                $response['expired_at'] = $transaction->getExpiredAt();
            }

            return $this->json($response);
        } catch (\Exception $e) {
            if ($e instanceof InsufficientAuthenticationException) {
                return $this->json([
                    'code' => 406,
                    'message' => 'На вашем счету недостаточно средств',
                ], 406);
            }

            throw new \Exception($e->getMessage());
        }
    }

    #[Route(path: '/', name: 'api_course_create', methods: ['POST'])]
    #[Security(name: "Bearer")]
    #[IsGranted("ROLE_SUPER_ADMIN")]
    public function create(
        Request $request,
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer,
        ValidatorInterface $validator
    ): JsonResponse
    {
        $courseDTO = $serializer->deserialize($request->getContent(), CreateCourseDTO::class, 'json');
        $errors = $validator->validate($courseDTO);
        if (count($errors) > 0) {
            return $this->json($errors, 422);
        }

        $type = CourseType::VALUES[$courseDTO->getType()];
        if (($type == CourseType::FULL_PAYMENT || $type == CourseType::RENTAL) && $courseDTO->getPrice() <= 0) {
            return $this->json(["detail" => "Course with Payment or Rental type must have price"], 422);
        }

        $course = Course::fromCreateDto($courseDTO);
        $entityManager->persist($course);
        $entityManager->flush();
        return $this->json(['success' => true], 201);
    }

    #[Route(path: '/{characterCode}', name: 'api_course_update', methods: ['PUT'])]
    #[Security(name: "Bearer")]
    #[IsGranted("ROLE_SUPER_ADMIN")]
    public function edit(
        string $characterCode,
        Request $request,
        CourseRepository $repository,
        SerializerInterface $serializer,
        ValidatorInterface $validator
    ): JsonResponse {
        $course = $repository->findOneBy(['character_code' => $characterCode]);

        if (!$course) {
            return $this->json(['message' => "Course doesn't exists"], 404);
        }

        if ($characterCode !== $course->getCharacterCode() &&
            $repository->count(['code' => $course->getCharacterCode()]) > 0) {
            return $this->json(['message' => 'Course with same code already exists'], 409);
        }

        $courseDTO = $serializer->deserialize($request->getContent(), CourseDTO::class, 'json');
        $errors = $validator->validate($courseDTO);
        if (count($errors) > 0) {
            return $this->json($errors, 422);
        }

        $requestType = json_decode($request->getContent(), true)['type'];
        $type = CourseType::VALUES[$requestType];
        $price = json_decode($request->getContent(), true)['price'];

        if (($type == CourseType::FULL_PAYMENT || $type == CourseType::RENTAL) && $price <= 0) {
            return $this->json([
                'message' => 'Course with Payment or Rental type must have price',
                'code' => 422],
                422
            );
        }

        $course->setCharacterCode($courseDTO->getCharacterCode());
        $course->setTitle($courseDTO->getTitle());
        $course->setType(CourseType::VALUES[$courseDTO->getType()]);
        $course->setPrice($courseDTO->getPrice());

        $repository->save($course);
        return $this->json(['success' => true], 200);
    }

    #[Route(path: '/{characterCode}/is-paid', name: 'api_course_id_paid', methods: ['GET'])]
    #[Security(name: "Bearer")]
    public function courseIsPaid(
        string $characterCode,
        PaymentService $paymentService,
        CourseRepository $courseRepository,
        UserService $userService,
    ): JsonResponse {
        $course = $courseRepository->findOneBy(['character_code' => $characterCode]);

        if (!$course) {
            return $this->json(['message' => "Course doesn't exists"], 422);
        }

        $isPaid = $paymentService->courseIsPaid($userService->getFromStorage()->getId(), $course);

        return $this->json(['message' => $isPaid], 200);
    }
}
