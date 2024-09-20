<?php

namespace App\DTO;

use App\Entity\User;
use JMS\Serializer\Annotation\SerializedName;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

class UserDTO
{
    #[SerializedName("email")]
    #[NotBlank]
    #[Email]
    public string $email;

    #[SerializedName("password")]
    public string $password;
}
