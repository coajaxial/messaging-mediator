<?php

namespace Coajaxial\MessagingMediator\Test\Unit\Testing;

use Coajaxial\MessagingMediator\MessageBus;
use Coajaxial\MessagingMediator\Testing\LazyMessageBus;
use Coajaxial\MessagingMediator\Testing\MessageBusUninitialized;
use PHPUnit\Framework\TestCase;
use stdClass;

class LazyMessageBusTest extends TestCase
{
    /**
     * @var LazyMessageBus
     */
    private $SUT;

    /**
     * @covers \Coajaxial\MessagingMediator\Testing\LazyMessageBus::dispatch
     */
    public function test_it_will_throw_on_dispatch_when_message_bus_is_uninitialized(): void
    {
        $this->expectException(MessageBusUninitialized::class);
        $this->SUT->dispatch(new stdClass());
    }

    /**
     * @covers \Coajaxial\MessagingMediator\Testing\LazyMessageBus::dispatch
     * @covers \Coajaxial\MessagingMediator\Testing\LazyMessageBus::initialize
     */
    public function test_it_will_dispatch_message_to_initialized_bus(): void
    {
        $message = new stdClass();

        $messageBus = $this->createMock(MessageBus::class);
        $messageBus->expects($this->once())
            ->method('dispatch')
            ->with(self::identicalTo($message));

        $this->SUT->initialize($messageBus);
        $this->SUT->dispatch($message);
    }

    /**
     * @covers \Coajaxial\MessagingMediator\Testing\LazyMessageBus::dispatch
     * @covers \Coajaxial\MessagingMediator\Testing\LazyMessageBus::initialize
     */
    public function test_it_will_return_result_of_inner_dispatch(): void
    {
        $message = new stdClass();

        $messageBus = $this->createMock(MessageBus::class);
        $messageBus
            ->method('dispatch')
            ->with(self::identicalTo($message))
            ->willReturn(42);

        $this->SUT->initialize($messageBus);
        /** @var int $result */
        $result = $this->SUT->dispatch($message);

        self::assertEquals(42, $result);
    }

    protected function setUp(): void
    {
        $this->SUT = new LazyMessageBus();
    }
}
