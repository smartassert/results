<?php

namespace App\ObjectFactory;

use Symfony\Component\Uid\Ulid;

class UlidFactory
{
    /**
     * @return non-empty-string
     */
    public function create(): string
    {
        return (string) new Ulid();
    }
}
