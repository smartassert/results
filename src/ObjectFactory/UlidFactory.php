<?php

namespace App\ObjectFactory;

use App\Exception\EmptyUlidException;
use Symfony\Component\Uid\Ulid;

class UlidFactory
{
    /**
     * @return non-empty-string
     *
     * @throws EmptyUlidException
     */
    public function create(): string
    {
        $ulid = (string) new Ulid();
        if ('' === $ulid) {
            throw new EmptyUlidException();
        }

        return $ulid;
    }
}
