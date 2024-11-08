<?php

namespace App\Controller\Api\V1;

use App\Enum\TransactionType;
use App\Repository\TransactionRepository;
use App\Service\RoundPrice;
use DateTimeInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Nelmio\ApiDocBundle\Attribute\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use OpenApi\Attributes as OA;

#[Route(path: "/api/v1/transactions")]
class TransactionController extends AbstractController
{
    private JWTTokenManagerInterface $jwtManager;
    private TokenStorageInterface $tokenStorageInterface;

    public function __construct(
        JWTTokenManagerInterface $jwtManager,
        TokenStorageInterface    $tokenStorageInterface
    ) {
        $this->jwtManager = $jwtManager;
        $this->tokenStorageInterface = $tokenStorageInterface;
    }
    
    #[OA\Get(
        path: "/api/v1/transactions/",
        description: "Get user transactions",
        parameters: [
            new OA\Parameter(
                name: "filter[type]",
                description: "Transaction type filter",
                in: "query",
                required: false,
            ),
            new OA\Parameter(
                name: "filter[course_code]",
                description: "Course code filter",
                in: "query",
                required: false,
            ),
            new OA\Parameter(
                name: "filter[skip_expired]",
                description: "Skip expired transactions filter (Rental Payment)",
                in: "query",
                required: false,
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Transactions info",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "id",type: "integer"),
                        new OA\Property(property: "created_at", type: "datetime"),
                        new OA\Property(property: "expires_at", type: "datetime"),
                        new OA\Property(property: "type", type: "string"),
                        new OA\Property(property: "course_code", type: "string"),
                        new OA\Property(property: "amount", type: "string"),
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
    #[Route(path: "/", methods: ['GET'], name: "api_user_transactions")]
    #[Security(name: 'Bearer')]
    public function userTransactions(
        Request $request,
        TransactionRepository $transactionRepository
    )
    {
        $token = $this->tokenStorageInterface->getToken();
        if (null === $token) {
            throw new UnauthorizedHttpException('Access token not presented');
        }

        $decodedJwtToken = $this->jwtManager->decode($token);

        $filter = [];

        if ($request->query->has('filter')) {
            $filter = array_map('htmlspecialchars', $request->query->all()['filter']);
        }

        $filter['type'] = $filter['type'] ?? null;

        if ($filter['type']) {
            $transactionType = TransactionType::valueFromName($filter['type'])->value;
        } else {
            $transactionType = null;
        }

        $courseCode = $filter['course_code'] ?? null;

        $filter['skip_expired'] = $filter['skip_expired'] ?? false;

        $transactions = $transactionRepository->getUserFilteredTransactions(
            $decodedJwtToken['username'],
            $transactionType,
            $courseCode,
            $filter['skip_expired']
        );

        foreach ($transactions as &$transaction) {
            $transaction['amount'] = RoundPrice::roundPrice($transaction['amount']);

            if ($transaction['type'] === TransactionType::Deposit) {
                unset($transaction['course_code']);
            }
            if (!$transaction['expires_at']) {
                unset($transaction['expires_at']);
            } else {
                $transaction['expires_at'] = $transaction['expires_at']->format(DateTimeInterface::ATOM);
            }
            $transaction['created_at'] = $transaction['created_at']->format(DateTimeInterface::ATOM);
            $transaction['type'] = TransactionType::tryFrom($transaction['type'])->getName();
        }

        return $this->json($transactions);
    }
}
