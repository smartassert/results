<?php

declare(strict_types=1);

namespace App\ArgumentResolver;

use App\Request\AddEventRequest;
use App\Request\AddEventRequestFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class AddEventRequestResolver implements ValueResolverInterface
{
    public function __construct(
        private readonly AddEventRequestFactory $addEventRequestFactory,
    ) {
    }

    /**
     * @return AddEventRequest[]
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if (
            'POST' !== $request->getMethod()
            || 'application/json' !== $request->headers->get('content-type')
            || AddEventRequest::class !== $argument->getType()
        ) {
            return [];
        }

        $requestData = json_decode($request->getContent(), true);
        $requestData = is_array($requestData) ? $requestData : [];

        return [$this->addEventRequestFactory->create($requestData)];
    }
}
