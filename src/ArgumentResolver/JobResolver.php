<?php

declare(strict_types=1);

namespace App\ArgumentResolver;

use App\Entity\Job;
use App\Repository\JobRepository;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class JobResolver implements ArgumentValueResolverInterface
{
    /**
     * @param non-empty-string[] $jobIdentifiers
     */
    public function __construct(
        private readonly JobRepository $jobRepository,
        private readonly array $jobIdentifiers = ['token', 'label'],
    ) {
    }

    public function supports(Request $request, ArgumentMetadata $argument): bool
    {
        return
            Job::class === $argument->getType()
            && $this->requestAttributesContainJobIdentifier($request->attributes);
    }

    /**
     * @return \Traversable<?Job>
     */
    public function resolve(Request $request, ArgumentMetadata $argument): \Traversable
    {
        foreach ($this->jobIdentifiers as $identifier) {
            if ($request->attributes->has($identifier)) {
                $value = $request->attributes->get($identifier);
                $value = is_string($value) ? trim($value) : '';

                if ('' !== $value) {
                    yield $this->jobRepository->findOneBy([$identifier => $value]);
                }
            }
        }

        yield null;
    }

    private function requestAttributesContainJobIdentifier(ParameterBag $attributes): bool
    {
        foreach ($this->jobIdentifiers as $identifier) {
            if ($attributes->has($identifier)) {
                return true;
            }
        }

        return false;
    }
}
