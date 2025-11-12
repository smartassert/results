<?php

namespace App\Entity;

interface JobInterface
{
    /**
     * @return non-empty-string
     */
    public function getLabel(): string;

    /**
     * @return non-empty-string
     */
    public function getUserId(): string;
}
