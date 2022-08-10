<?php

namespace App\Request;

class AddEventRequestFactory
{
    /**
     * @param array<mixed> $data
     */
    public function create(array $data): AddEventRequest
    {
        $headerSection = $data[AddEventRequest::KEY_HEADER_SECTION] ?? [];
        $headerSection = is_array($headerSection) ? $headerSection : [];

        $sequenceNumber = $headerSection[AddEventRequest::KEY_SEQUENCE_NUMBER] ?? null;
        $sequenceNumber = is_int($sequenceNumber) && $sequenceNumber > 0 ? $sequenceNumber : null;

        $type = $this->getNonEmptyStringFromArray($headerSection, AddEventRequest::KEY_TYPE);
        $label = $this->getNonEmptyStringFromArray($headerSection, AddEventRequest::KEY_LABEL);
        $reference = $this->getNonEmptyStringFromArray($headerSection, AddEventRequest::KEY_REFERENCE);

        $body = $data[AddEventRequest::KEY_BODY] ?? [];
        $body = is_array($body) ? $body : [];

        return new AddEventRequest($sequenceNumber, $type, $label, $reference, $body);
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
