<?php

namespace App\Services;

use App\Contracts\VisitTypeHandler;
use App\Enums\VisitStatus;
use App\Exceptions\InvalidVisitTransitionException;
use App\Models\Visit;
use App\Workflows\VisitHandlerFactory;

class VisitWorkflowService
{
    public function __construct(private readonly VisitTypeHandler $handler)
    {
    }

    public static function for(Visit $visit): self
    {
        return new self(VisitHandlerFactory::for($visit));
    }

    /** @return array<string, string> */
    public function steps(): array
    {
        return $this->handler->workflowSteps();
    }

    public function currentStatus(Visit $visit): ?VisitStatus
    {
        return VisitStatus::tryFrom($visit->status);
    }

    /** @return array<VisitStatus> */
    public function allowedTransitions(Visit $visit): array
    {
        return $this->handler->allowedTransitions($visit);
    }

    public function canTransition(Visit $visit, VisitStatus $target): bool
    {
        return in_array($target, $this->allowedTransitions($visit), true);
    }

    public function transition(Visit $visit, VisitStatus|string $target): void
    {
        $status = $target instanceof VisitStatus
            ? $target
            : VisitStatus::from($target);

        if (config('visits.enforce_workflow_transitions') && ! $this->canTransition($visit, $status)) {
            throw new InvalidVisitTransitionException($visit, $status);
        }

        $visit->update(['status' => $status->value]);
    }
}
