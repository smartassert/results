<?php

namespace App\ObjectFactory;

use App\Entity\JobInterface;
use App\Model\Job;

interface JobFactoryInterface
{
    public function create(JobInterface $job): Job;
}
