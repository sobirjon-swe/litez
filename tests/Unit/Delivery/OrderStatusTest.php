<?php

namespace Tests\Unit\Delivery;

use App\Modules\Delivery\Enums\OrderStatus;
use PHPUnit\Framework\TestCase;

class OrderStatusTest extends TestCase
{
    public function test_pending_can_transition_to_paid(): void
    {
        $this->assertTrue(OrderStatus::Pending->canTransitionTo(OrderStatus::Paid));
    }

    public function test_pending_can_transition_to_cancelled(): void
    {
        $this->assertTrue(OrderStatus::Pending->canTransitionTo(OrderStatus::Cancelled));
    }

    public function test_pending_cannot_transition_to_delivered(): void
    {
        $this->assertFalse(OrderStatus::Pending->canTransitionTo(OrderStatus::Delivered));
    }

    public function test_paid_can_transition_to_in_delivery(): void
    {
        $this->assertTrue(OrderStatus::Paid->canTransitionTo(OrderStatus::InDelivery));
    }

    public function test_in_delivery_can_transition_to_delivered(): void
    {
        $this->assertTrue(OrderStatus::InDelivery->canTransitionTo(OrderStatus::Delivered));
    }

    public function test_delivered_is_terminal(): void
    {
        $this->assertEmpty(OrderStatus::Delivered->allowedTransitions());
    }

    public function test_cancelled_is_terminal(): void
    {
        $this->assertEmpty(OrderStatus::Cancelled->allowedTransitions());
    }
}
