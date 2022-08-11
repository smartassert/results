<?php

declare(strict_types=1);

namespace App\ArgumentResolver;

use App\Entity\Reference;
use App\Repository\ReferenceRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class ReferenceResolver implements ArgumentValueResolverInterface
{
    public function __construct(
        private readonly ReferenceRepository $repository,
    ) {
    }

    public function supports(Request $request, ArgumentMetadata $argument): bool
    {
        return Reference::class === $argument->getType() && $request->attributes->has('reference');
    }

    /**
     * @return \Traversable<?Reference>
     */
    public function resolve(Request $request, ArgumentMetadata $argument): \Traversable
    {
        $requestReference = $request->attributes->get('reference');
        $requestReference = is_string($requestReference) ? trim($requestReference) : '';

        yield '' === $requestReference ? null : $this->repository->findOneBy(['reference' => $requestReference]);
    }
}
