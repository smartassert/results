<?php

declare(strict_types=1);

namespace App\ArgumentResolver;

use App\Request\AddEventRequest;
use App\Request\AddEventRequestFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class AddEventRequestResolver implements ArgumentValueResolverInterface
{
    public function __construct(
        private readonly AddEventRequestFactory $addEventRequestFactory,
    ) {
    }

    public function supports(Request $request, ArgumentMetadata $argument): bool
    {
        return
            'POST' === $request->getMethod()
            && 'application/json' === $request->headers->get('content-type')
            && AddEventRequest::class === $argument->getType();
    }

    /**
     * @return \Traversable<AddEventRequest>
     */
    public function resolve(Request $request, ArgumentMetadata $argument): \Traversable
    {
        if ($this->supports($request, $argument)) {
            $requestData = json_decode($request->getContent(), true);
            $requestData = is_array($requestData) ? $requestData : [];

            yield $this->addEventRequestFactory->create($requestData);
        }
    }
}
