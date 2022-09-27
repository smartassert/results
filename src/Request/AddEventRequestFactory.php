<?php

namespace App\Request;

class AddEventRequestFactory
{
    /**
     * @param array<mixed> $data
     */
    public function create(array $data): AddEventRequest
    {
        $sequenceNumber = $data[AddEventRequest::KEY_SEQUENCE_NUMBER] ?? null;
        $sequenceNumber = is_int($sequenceNumber) && $sequenceNumber > 0 ? $sequenceNumber : null;

        $type = $this->getNonEmptyStringFromArray($data, AddEventRequest::KEY_TYPE);
        $label = $this->getNonEmptyStringFromArray($data, AddEventRequest::KEY_LABEL);
        $reference = $this->getNonEmptyStringFromArray($data, AddEventRequest::KEY_REFERENCE);

        $relatedReferences = $data[AddEventRequest::KEY_RELATED_REFERENCES] ?? null;
        $relatedReferences = is_array($relatedReferences) ? $relatedReferences : null;

        $body = $data[AddEventRequest::KEY_BODY] ?? null;
        $body = is_array($body) ? $body : null;

        return new AddEventRequest($sequenceNumber, $type, $label, $reference, $relatedReferences, $body);
    }

    /**
     * @param array<mixed>     $data
     * @param non-empty-string $key
     *
     * @return null|non-empty-string
     */
    private function getNonEmptyStringFromArray(array $data, string $key): ?string
    {
        $value = $data[$key] ?? null;
        $value = is_string($value) ? trim($value) : null;

        return '' === $value ? null : $value;
    }
}
