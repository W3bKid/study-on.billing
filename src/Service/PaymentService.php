<?php

namespace App\Service;

use App\Entity\Course;
use App\Entity\Transaction;
use App\Entity\User;
use App\Enum\CourseType;
use App\Enum\TransactionType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Exception\InsufficientAuthenticationException;

class PaymentService
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function pay(User $user, Course $course): Transaction
    {
        if ($user->getBalance() < $course->getPrice()) {
            throw new InsufficientAuthenticationException();
        }

        $transactionTime = (new \DateTime());

        $transaction = (new Transaction())
            ->setType(TransactionType::Payment)
            ->setCourse($course)
            ->setDate($transactionTime)
            ->setValue($course->getPrice())
            ->setBillingUser($user);

        if ($course->getType() == CourseType::RENTAL->value) {
            $transaction->setExpiredAt($transactionTime->modify('+1 week'));
        }

        if ($this->courseIsPaid($user->getId(), $course)) {

            return $transaction;
        }
        $this->entityManager->wrapInTransaction(function () use ($transaction, $course, $user) {
            $user->setBalance($user->getBalance() - $course->getPrice());
            $this->entityManager->persist($transaction);
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        });

        return $transaction;
    }

    public function deposit(User $user, float $amount): User
    {
        if ($amount < 0) {
            throw new \Exception('Депозит не может быть отрицательным');
        }

        $this->entityManager->wrapInTransaction(function () use ($user, $amount) {
            $transaction = (new Transaction())
                ->setType(TransactionType::Deposit)
                ->setDate(new \DateTime())
                ->setValue($amount)
                ->setBillingUser($user);

            $user = $user->setBalance(round($user->getBalance() + $amount, 2));
            $this->entityManager->persist($transaction);
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        });

        return $user;
    }

    public function courseIsPaid(int $userId, Course $course): bool|Transaction
    {
        $transactions = $this->entityManager
            ->getRepository(Transaction::class)
            ->findByUserAndCourse($userId, $course->getId());

        if(!$transactions) {
            return false;
        }

        if ($course->getType() == CourseType::RENTAL->value) {
            return $transactions[0]->getExpiredAt() >= new \DateTime();
        }

        if ($transactions[0]->getType() == TransactionType::Payment->value) {
            return true;
        }

        return false;
    }
}