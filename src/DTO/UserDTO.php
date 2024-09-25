<?php

namespace App\DTO;

use App\Entity\User;
use JMS\Serializer\Annotation\SerializedName;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class UserDTO
{
    #[SerializedName("email")]
    #[NotBlank]
    #[Email]
    public string $email;

    #[SerializedName("password")]
    #[NotBlank]
    #[
        Length(
            min: 6,
            max: 255,
            minMessage: "Password id too short",
            maxMessage: "Password is too long"
        )
    ]
    public string $password;
}
