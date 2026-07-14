<?php

declare(strict_types=1);

namespace App\ValueResolver;

use App\Request\CreateJobRequest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

final readonly class CreateJobRequestResolver implements ValueResolverInterface
{
    /**
     * @return CreateJobRequest[]
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if ('POST' !== $request->getMethod() || CreateJobRequest::class !== $argument->getType()) {
            return [];
        }

        $label = $request->attributes->get(CreateJobRequest::KEY_LABEL);
        $label = is_string($label) ? trim($label) : null;
        $label = '' === $label ? null : $label;

        $notifyUrl = $request->request->get(CreateJobRequest::KEY_NOTIFY_URL);
        $notifyUrl = is_string($notifyUrl) ? trim($notifyUrl) : null;
        $notifyUrl = '' === $notifyUrl ? null : $notifyUrl;

        return [new CreateJobRequest($label, $notifyUrl)];
    }
}
