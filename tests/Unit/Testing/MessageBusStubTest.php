<?php

namespace Coajaxial\MessagingMediator\Test\Unit\Testing;

use Coajaxial\MessagingMediator\Testing\MessageBusStub;
use Coajaxial\MessagingMediator\Testing\UnhandledMessagesLeft;
use PHPUnit\Framework\TestCase;
use UnderflowException;

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
        $this->SUT->dispatch(new MessageBusStubTest_MessageA());

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
     * @uses   \Coajaxial\MessagingMediator\Testing\MessageBusStub::dispatch()
     * @uses   \Coajaxial\MessagingMediator\Testing\UnhandledMessagesLeft::messages()
     */
    public function test_it_will_not_clear_unhandled_messages(): void
    {
        $this->SUT->dispatch(new MessageBusStubTest_MessageA());

        try {
            $this->SUT->ensureNoUnhandledMessagesLeft();
            self::fail(sprintf('Expected exception of type %s', UnhandledMessagesLeft::class));
        } catch (UnhandledMessagesLeft $e) {
        }

        $this->expectException(UnhandledMessagesLeft::class);
        $this->SUT->ensureNoUnhandledMessagesLeft();
    }

    /**
     * @covers \Coajaxial\MessagingMediator\Testing\MessageBusStub::popUnhandledMessage
     * @uses   \Coajaxial\MessagingMediator\Testing\MessageBusStub::dispatch()
     */
    public function test_it_can_pop_unhandled_messages_and_return_them(): void
    {
        $this->SUT->dispatch(new MessageBusStubTest_MessageA());
        $this->SUT->dispatch(new MessageBusStubTest_MessageB());

        self::assertInstanceOf(MessageBusStubTest_MessageA::class, $this->SUT->popUnhandledMessage());
        self::assertInstanceOf(MessageBusStubTest_MessageB::class, $this->SUT->popUnhandledMessage());
    }

    /**
     * @covers \Coajaxial\MessagingMediator\Testing\MessageBusStub::popUnhandledMessage
     * @uses   \Coajaxial\MessagingMediator\Testing\MessageBusStub::dispatch()
     */
    public function test_it_throws_when_pop_causes_underflow(): void
    {
        $this->SUT->dispatch(new MessageBusStubTest_MessageA());
        $this->SUT->dispatch(new MessageBusStubTest_MessageB());

        $this->expectException(UnderflowException::class);
        array_map([$this->SUT, 'popUnhandledMessage'], range(1, 3));
    }

    protected function setUp(): void
    {
        $this->SUT = new MessageBusStub();
    }
}

final class MessageBusStubTest_MessageA
{
}

final class MessageBusStubTest_MessageB
{
}
