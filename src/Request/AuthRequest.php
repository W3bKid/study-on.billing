<?php

namespace App\Request;

use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

class AuthRequest extends BaseRequest
{
    #[Email]
    #[NotBlank]
    protected $email;

    #[NotBlank]
    protected $password;
}
