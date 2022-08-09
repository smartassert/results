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
            $sequenceNumber = $request->request->get(AddEventRequest::KEY_SEQUENCE_NUMBER);
            if (null !== $sequenceNumber) {
                $sequenceNumber = is_int($sequenceNumber) || ctype_digit($sequenceNumber)
                    ? (int) $sequenceNumber
                    : null;

                if (is_int($sequenceNumber) && $sequenceNumber <= 1) {
                    $sequenceNumber = null;
                }
            }

            $type = $request->request->get(AddEventRequest::KEY_TYPE);
            $type = is_string($type) ? trim($type) : null;
            $type = '' === $type ? null : $type;

            $label = $request->request->get(AddEventRequest::KEY_LABEL);
            $label = is_string($label) ? trim($label) : null;
            $label = '' === $label ? null : $label;

            $reference = $request->request->get(AddEventRequest::KEY_REFERENCE);
            $reference = is_string($reference) ? trim($reference) : null;
            $reference = '' === $reference ? null : $reference;

            $payload = null;
            $payloadContent = $request->request->get(AddEventRequest::KEY_PAYLOAD);
            if (is_string($payloadContent)) {
                $payload = json_decode($payloadContent, true);
                $payload = is_array($payload) ? $payload : null;
            }

            yield new AddEventRequest($sequenceNumber, $type, $label, $reference, $payload);
        }
    }
}
