<?php

namespace App\Request;

class AddEventRequestValidator
{
    /**
     * @throws InvalidAddEventRequestException
     */
    public function validate(AddEventRequest $request): ValidatedAddEventRequest
    {
        if (null === $request->job) {
            throw new InvalidAddEventRequestException(AddEventRequest::KEY_JOB, 'a string');
        }

        if (null === $request->sequenceNumber) {
            throw new InvalidAddEventRequestException(AddEventRequest::KEY_SEQUENCE_NUMBER, 'a positive integer');
        }

        if (null === $request->type) {
            throw new InvalidAddEventRequestException(AddEventRequest::KEY_TYPE, 'a string');
        }

        if (null === $request->label) {
            throw new InvalidAddEventRequestException(AddEventRequest::KEY_LABEL, 'a string');
        }

        if (null === $request->reference) {
            throw new InvalidAddEventRequestException(AddEventRequest::KEY_REFERENCE, 'a string');
        }

        return new ValidatedAddEventRequest(
            $request->job,
            $request->sequenceNumber,
            $request->type,
            $request->label,
            $request->reference,
            $request->relatedReferences,
            $request->body,
        );
    }
}
