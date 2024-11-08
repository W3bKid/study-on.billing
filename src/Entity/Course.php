<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Enum\CourseType;
use App\Repository\CourseRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CourseRepository::class)]
#[ApiResource]
class Course
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    #[Assert\Length(
        max: 255,
        maxMessage: "Символьный код должен быть не длиннее {{ limit }} символов"
    )]
    #[Assert\NotBlank(
        message: "Поле обязательно должно быть заполнено"
    )]
    private ?string $character_code = null;

    #[ORM\Column(type: Types::SMALLINT)]
    private ?int $type = null;

    #[ORM\Column]
    private ?float $price = null;

    /**
     * @var Collection<int, Transaction>
     */
    #[ORM\OneToMany(targetEntity: Transaction::class, mappedBy: 'course')]
    private Collection $transactions;

    #[ORM\Column(length: 255)]
    #[Assert\Length(
        max: 255,
        maxMessage: "Символьный код должен быть не длиннее {{ limit }} символов"
    )]
    #[Assert\NotBlank(
        message: "Поле обязательно должно быть заполнено"
    )]
    private ?string $title = null;

    public function __construct()
    {
        $this->transactions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCharacterCode(): ?string
    {
        return $this->character_code;
    }

    public function setCharacterCode(string $character_code): static
    {
        $this->character_code = $character_code;

        return $this;
    }

    public function getType(): ?int
    {
        return $this->type;
    }

    public function setType(CourseType $type): static
    {
        $this->type = $type->value;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): static
    {
        $this->price = $price;

        return $this;
    }

    /**
     * @return Collection<int, Transaction>
     */
    public function getTransactions(): Collection
    {
        return $this->transactions;
    }

    public function addTransaction(Transaction $transaction): static
    {
        if (!$this->transactions->contains($transaction)) {
            $this->transactions->add($transaction);
            $transaction->setCourse($this);
        }

        return $this;
    }

    public function removeTransaction(Transaction $transaction): static
    {
        if ($this->transactions->removeElement($transaction)) {
            // set the owning side to null (unless already changed)
            if ($transaction->getCourse() === $this) {
                $transaction->setCourse(null);
            }
        }

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }
}
