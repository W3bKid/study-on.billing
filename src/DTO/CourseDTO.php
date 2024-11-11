<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use JMS\Serializer\Annotation as Serializer;

class CourseDTO {

    #[Serializer\Type("string")]
    #[NotBlank]
    #[Length(
        min: 1,
        max: 255,
        minMessage: 'Название курса не должно быть пустым',
        maxMessage: 'Название курса не должно быть длиннее 255 символов'
    )]
    public string $characterCode;

    #[Serializer\Type("float")]
    #[GreaterThan(0)]
    public float $price;

    #[Serializer\Type("string")]
    public string $type;

    #[Serializer\Type("string")]
    #[NotBlank]
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

    public function setType(string $type): CourseDTO
    {
        $this->type = $type;
        return $this;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function setPrice(float $price): CourseDTO
    {
        $this->price = $price;
        return $this;
    }

    public function getCharacterCode(): string
    {
        return $this->characterCode;
    }

    public function setCharacterCode(string $characterCode): CourseDTO
    {
        $this->characterCode = $characterCode;
        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): CourseDTO
    {
        $this->title = $title;
        return $this;
    }
}
