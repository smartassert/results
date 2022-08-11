<?php

declare(strict_types=1);

namespace App\ArgumentResolver;

use App\Entity\Job;
use App\Repository\JobRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class JobResolver implements ArgumentValueResolverInterface
{
    public function __construct(
        private readonly JobRepository $jobRepository,
    ) {
    }

    public function supports(Request $request, ArgumentMetadata $argument): bool
    {
        return
            Job::class === $argument->getType()
            && ($request->attributes->has('token') || $request->attributes->has('label'));
    }

    /**
     * @return \Traversable<?Job>
     */
    public function resolve(Request $request, ArgumentMetadata $argument): \Traversable
    {
        $requestToken = $request->attributes->get('token');
        $requestToken = is_string($requestToken) ? trim($requestToken) : '';

        if ('' !== $requestToken) {
            yield $this->jobRepository->findOneBy(['token' => $requestToken]);
        }

        $requestJob = $request->attributes->get('label');
        $requestJob = is_string($requestJob) ? trim($requestJob) : '';

        if ('' !== $requestJob) {
            yield $this->jobRepository->findOneBy(['label' => $requestJob]);
        }

        yield null;
    }
}
