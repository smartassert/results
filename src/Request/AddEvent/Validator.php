<?php

namespace App\Request\AddEvent;

class Validator
{
    /**
     * @throws InvalidRequestException
     */
    public function validate(Request $request): ValidatedRequest
    {
        if (null === $request->job) {
            throw new InvalidRequestException(Request::KEY_JOB, 'a string');
        }

        if (null === $request->sequenceNumber) {
            throw new InvalidRequestException(Request::KEY_SEQUENCE_NUMBER, 'a positive integer');
        }

        if (null === $request->type) {
            throw new InvalidRequestException(Request::KEY_TYPE, 'a string');
        }

        if (null === $request->label) {
            throw new InvalidRequestException(Request::KEY_LABEL, 'a string');
        }

        if (null === $request->reference) {
            throw new InvalidRequestException(Request::KEY_REFERENCE, 'a string');
        }

        return new ValidatedRequest(
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
