<?php

namespace App\ObjectFactory;

use App\Entity\JobInterface;
use App\Model\SerializableJobInterface;

interface SerializableJobFactoryInterface
{
    public function createForNewJob(JobInterface $job): SerializableJobInterface;

    public function createForExistingJob(JobInterface $job): SerializableJobInterface;
}
