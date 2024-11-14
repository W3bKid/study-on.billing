<?php

namespace App\DTO\Create;

use App\Entity\Course;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Contracts\Service\Attribute\Required;

#[UniqueEntity(entityClass: Course::class, fields: ['character_code'])]
class CreateCourseDTO {
    #[Serializer\Type("string")]
    #[NotBlank]
    #[Length(
        min: 1,
        max: 255,
        minMessage: 'Название курса не должно быть пустым',
        maxMessage: 'Название курса не должно быть длиннее 255 символов'
    )]
    public string $character_code;

//    #[Serializer\Type("float")]
    public float|null $price = null;

    #[Serializer\Type("string")]
    #[Choice(choices: ['Free', 'Rental', 'Full Payment'])]
    public string $type;

    #[Serializer\Type("string")]
    #[NotBlank]
    #[Required]
    #[Length(
        min: 1,
        max: 255,
        minMessage: 'Название курса не должно быть пустым',
        maxMessage: 'Название курса не должно быть длиннее 255 символов'
    )]
    public string $title;

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): CreateCourseDTO
    {
        $this->type = $type;
        return $this;
    }

    public function getPrice(): float|null
    {
        return $this->price;
    }

    public function setPrice(float $price): CreateCourseDTO
    {
        $this->price = $price;
        return $this;
    }

    public function getCharacterCode(): string
    {
        return $this->character_code;
    }

    public function setCharacterCode(string $character_code): CreateCourseDTO
    {
        $this->character_code = $character_code;
        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): CreateCourseDTO
    {
        $this->title = $title;
        return $this;
    }
}
