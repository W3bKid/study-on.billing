<?php

namespace App\Request;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class BaseRequest
{
    public function __construct(protected ValidatorInterface $validator)
    {
        $this->populate();

        if ($this->autoValidateRequest()) {
            $this->validate();
        }
    }

    public function __call($name, $argument)
    {
        if (substr($name, 0, 3) === "get") {
            $param = strtolower(substr($name, 3));
            return $this->{$param};
        }
    }

    public function validate()
    {
        $errors = $this->validator->validate($this);

        $messages = ["message" => "validation_failed", "errors" => []];

        foreach ($errors as $message) {
            $messages["errors"][] = [
                "property" => $message->getPropertyPath(),
                "value" => $message->getInvalidValue(),
                "message" => $message->getMessage(),
            ];
        }

        if (count($messages["errors"]) > 0) {
            $response = new JsonResponse($messages);
            $response->send();

            exit();
        }
    }

    public function getRequest(): Request
    {
        return Request::createFromGlobals();
    }

    protected function populate(): void
    {
        $requestFields = $this->getRequest()->toArray();
        foreach (get_object_vars($this) as $attribute => $_) {
            if (isset($requestFields[$attribute])) {
                $this->{$attribute} = $requestFields[$attribute];
            }
        }
    }

    protected function autoValidateRequest(): bool
    {
        return true;
    }
}
