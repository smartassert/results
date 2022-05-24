<?php

declare(strict_types=1);

namespace App\ArgumentResolver;

use App\Request\AddEventRequest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class AddEventRequestResolver implements ArgumentValueResolverInterface
{
    public function supports(Request $request, ArgumentMetadata $argument): bool
    {
        return 'POST' === $request->getMethod() && AddEventRequest::class === $argument->getType();
    }

    /**
     * @return \Traversable<AddEventRequest>
     */
    public function resolve(Request $request, ArgumentMetadata $argument): \Traversable
    {
        if ($this->supports($request, $argument)) {
            $type = $request->request->get(AddEventRequest::KEY_TYPE);
            $type = is_string($type) ? trim($type) : null;

            $reference = $request->request->get(AddEventRequest::KEY_REFERENCE);
            $reference = is_string($reference) ? trim($reference) : null;

            $payload = null;
            $payloadContent = $request->request->get(AddEventRequest::KEY_PAYLOAD);
            if (is_string($payloadContent)) {
                $payload = json_decode($payloadContent, true);
                $payload = is_array($payload) ? $payload : null;
            }

            yield new AddEventRequest($type, $reference, $payload);
        }
    }
}
