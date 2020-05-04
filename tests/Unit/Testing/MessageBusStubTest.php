<?php

namespace Coajaxial\MessagingMediator\Test\Unit\Testing;

use Coajaxial\MessagingMediator\Testing\MessageBusStub;
use Coajaxial\MessagingMediator\Testing\UnhandledMessagesLeft;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * @covers \Coajaxial\MessagingMediator\Testing\MessageBusStub::__construct
 */
class MessageBusStubTest extends TestCase
{
    /**
     * @var MessageBusStub
     */
    private $SUT;

    /**
     * @covers \Coajaxial\MessagingMediator\Testing\MessageBusStub::dispatch
     * @uses   \Coajaxial\MessagingMediator\Testing\UnhandledMessagesLeft::messages
     * @covers \Coajaxial\MessagingMediator\Testing\MessageBusStub::ensureNoUnhandledMessagesLeft
     */
    public function test_it_will_throw_when_there_are_unhandled_messages_left(): void
    {
        $this->SUT->dispatch(new stdClass());

        $this->expectException(UnhandledMessagesLeft::class);
        $this->SUT->ensureNoUnhandledMessagesLeft();
    }

    /**
     * @covers \Coajaxial\MessagingMediator\Testing\MessageBusStub::ensureNoUnhandledMessagesLeft
     */
    public function test_it_will_not_throw_when_there_are_no_unhandled_messages_left(): void
    {
        $this->SUT->ensureNoUnhandledMessagesLeft();
        self::assertTrue(true);
    }

    /**
     * @covers \Coajaxial\MessagingMediator\Testing\MessageBusStub::ensureNoUnhandledMessagesLeft
     * @uses \Coajaxial\MessagingMediator\Testing\MessageBusStub::dispatch()
     * @uses \Coajaxial\MessagingMediator\Testing\UnhandledMessagesLeft::messages()
     */
    public function test_it_will_not_clear_unhandled_messages(): void
    {
        $this->SUT->dispatch(new stdClass());

        try {
            $this->SUT->ensureNoUnhandledMessagesLeft();
            self::fail(sprintf('Expected exception of type %s', UnhandledMessagesLeft::class));
        } catch (UnhandledMessagesLeft $e) {
        }

        $this->expectException(UnhandledMessagesLeft::class);
        $this->SUT->ensureNoUnhandledMessagesLeft();
    }

    protected function setUp(): void
    {
        $this->SUT = new MessageBusStub();
    }
}
