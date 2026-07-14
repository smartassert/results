<?php

declare(strict_types=1);

namespace App\ValueResolver;

use App\Repository\JobRepository;
use App\Repository\ReferenceRepository;
use App\Request\ListEventsRequest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

final readonly class ListEventsRequestResolver implements ValueResolverInterface
{
    public function __construct(
        private JobRepository $jobRepository,
        private ReferenceRepository $referenceRepository,
    ) {}

    /**
     * @return ListEventsRequest[]
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if (ListEventsRequest::class !== $argument->getType()) {
            return [];
        }

        $jobLabel = $request->attributes->get('label');
        $job = $this->jobRepository->findOneBy(['label' => $jobLabel]);

        $eventReference = $request->query->get('reference');
        $reference = $this->referenceRepository->findOneBy(['reference' => $eventReference]);

        $eventType = $request->query->get('type');
        $eventType = is_string($eventType) ? $eventType : null;

        return [
            new ListEventsRequest(
                $job,
                $reference,
                $eventType,
                $request->query->has('reference'),
            ),
        ];
    }
}
