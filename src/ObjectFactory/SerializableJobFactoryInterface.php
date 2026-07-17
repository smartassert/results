<?php

namespace App\ObjectFactory;

use App\Entity\JobInterface;
use App\Model\SerializableJobInterface;

interface SerializableJobFactoryInterface
{
    public function create(JobInterface $job): SerializableJobInterface;
}
