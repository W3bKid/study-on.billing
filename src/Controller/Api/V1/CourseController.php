<?php

namespace App\Controller\Api\V1;

use App\Enum\CourseType;
use App\Repository\CourseRepository;
use App\Service\PaymentService;
use App\Service\RoundPrice;
use App\Service\UserService;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use Nelmio\ApiDocBundle\Attribute\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\InsufficientAuthenticationException;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use OpenApi\Attributes as OA;

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
    #[Route(path: "/")]
    #[Security(name: 'Bearer')]
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
    #[Route(path: '/{code}/pay', name: 'api_courses_pay', methods: ['GET'])]
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
}
