<?php

declare(strict_types=1);

namespace App\ArgumentResolver;

use App\Entity\Job;
use App\Repository\JobRepository;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class JobResolver implements ValueResolverInterface
{
    /**
     * @param non-empty-string[] $jobIdentifiers
     */
    public function __construct(
        private readonly JobRepository $jobRepository,
        private readonly array $jobIdentifiers = ['token', 'label'],
    ) {
    }

    /**
     * @return array<null|Job>
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if (
            Job::class !== $argument->getType()
            || !$this->requestAttributesContainJobIdentifier($request->attributes)
        ) {
            return [];
        }

        foreach ($this->jobIdentifiers as $identifier) {
            if ($request->attributes->has($identifier)) {
                $value = $request->attributes->get($identifier);
                $value = is_string($value) ? trim($value) : '';

                if ('' !== $value) {
                    return [$this->jobRepository->findOneBy([$identifier => $value])];
                }
            }
        }

        return [];
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
