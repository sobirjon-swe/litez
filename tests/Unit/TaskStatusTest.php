<?php

namespace Tests\Unit;

use App\Enums\TaskStatus;
use PHPUnit\Framework\TestCase;

class TaskStatusTest extends TestCase
{
    public function test_pending_can_transition_to_in_progress(): void
    {
        $this->assertTrue(TaskStatus::Pending->canTransitionTo(TaskStatus::InProgress));
    }

    public function test_pending_can_transition_to_cancelled(): void
    {
        $this->assertTrue(TaskStatus::Pending->canTransitionTo(TaskStatus::Cancelled));
    }

    public function test_pending_cannot_transition_to_done(): void
    {
        $this->assertFalse(TaskStatus::Pending->canTransitionTo(TaskStatus::Done));
    }

    public function test_in_progress_can_transition_to_done(): void
    {
        $this->assertTrue(TaskStatus::InProgress->canTransitionTo(TaskStatus::Done));
    }

    public function test_in_progress_can_transition_to_cancelled(): void
    {
        $this->assertTrue(TaskStatus::InProgress->canTransitionTo(TaskStatus::Cancelled));
    }

    public function test_done_is_terminal(): void
    {
        $this->assertEmpty(TaskStatus::Done->allowedTransitions());
        $this->assertFalse(TaskStatus::Done->canTransitionTo(TaskStatus::Pending));
        $this->assertFalse(TaskStatus::Done->canTransitionTo(TaskStatus::InProgress));
    }

    public function test_cancelled_is_terminal(): void
    {
        $this->assertEmpty(TaskStatus::Cancelled->allowedTransitions());
        $this->assertFalse(TaskStatus::Cancelled->canTransitionTo(TaskStatus::Pending));
    }
}
