<?php

declare(strict_types=1);

namespace App\ArgumentResolver;

use App\Request\AddEventRequest;
use Symfony\Component\HttpFoundation\ParameterBag;
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
            $data = $request->request;

            $sequenceNumber = $this->getPositiveIntegerFromRequestPayload($data, AddEventRequest::KEY_SEQUENCE_NUMBER);
            $type = $this->getNonEmptyStringFromRequestPayload($data, AddEventRequest::KEY_TYPE);
            $label = $this->getNonEmptyStringFromRequestPayload($data, AddEventRequest::KEY_LABEL);
            $reference = $this->getNonEmptyStringFromRequestPayload($data, AddEventRequest::KEY_REFERENCE);

            $payload = null;
            $payloadContent = $data->get(AddEventRequest::KEY_PAYLOAD);
            if (is_string($payloadContent)) {
                $payload = json_decode($payloadContent, true);
                $payload = is_array($payload) ? $payload : null;
            }

            yield new AddEventRequest($sequenceNumber, $type, $label, $reference, $payload);
        }
    }

    /**
     * @param non-empty-string$key
     *
     * @return null|positive-int
     */
    private function getPositiveIntegerFromRequestPayload(ParameterBag $data, string $key): ?int
    {
        $value = $data->get($key);
        if ((is_string($value) && ctype_digit($value)) || is_int($value)) {
            $value = (int) $value;
        }

        return is_int($value) && $value > 0 ? $value : null;
    }

    /**
     * @param non-empty-string $key
     *
     * @return null|non-empty-string
     */
    private function getNonEmptyStringFromRequestPayload(ParameterBag $data, string $key): ?string
    {
        $value = $data->get($key);
        $value = is_string($value) ? trim($value) : null;

        return '' === $value ? null : $value;
    }
}
