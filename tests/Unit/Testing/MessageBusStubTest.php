<?php

namespace Coajaxial\MessagingMediator\Test\Unit\Testing;

use Coajaxial\MessagingMediator\Testing\HandlerStub;
use Coajaxial\MessagingMediator\Testing\MessageBusStub;
use Coajaxial\MessagingMediator\Testing\MultipleHandlerStubsMatchTheSameMessage;
use Coajaxial\MessagingMediator\Testing\UnhandledMessagesLeft;
use PHPUnit\Framework\TestCase;
use stdClass;
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

        $this->SUT->popUnhandledMessage();
        $this->SUT->popUnhandledMessage();

        $this->expectException(UnderflowException::class);
        $this->SUT->popUnhandledMessage();
    }

    /**
     * @covers \Coajaxial\MessagingMediator\Testing\MessageBusStub::activate
     * @covers \Coajaxial\MessagingMediator\Testing\MessageBusStub::dispatch
     */
    public function test_it_invokes_activated_stub_on_dispatch_if_matching(): void
    {
        $message = new MessageBusStubTest_MessageA();

        $stub = $this->createMock(HandlerStub::class);
        $stub->method('matches')
            ->with(self::identicalTo($message))
            ->willReturn(true);

        $stub->expects(self::once())
            ->method('invoke')
            ->with(self::identicalTo($message));

        $this->SUT->activate($stub);

        $this->SUT->dispatch($message);
    }

    /**
     * @covers \Coajaxial\MessagingMediator\Testing\MessageBusStub::activate
     * @covers \Coajaxial\MessagingMediator\Testing\MessageBusStub::dispatch
     */
    public function test_it_will_return_return_value_of_matching_stub_on_dispatch(): void
    {
        $message = new MessageBusStubTest_MessageA();

        $stub = $this->createMock(HandlerStub::class);
        $stub->method('matches')
            ->with(self::identicalTo($message))
            ->willReturn(true);

        $stub->method('invoke')
            ->with(self::identicalTo($message))
            ->willReturn(42);

        $this->SUT->activate($stub);

        self::assertEquals(42, $this->SUT->dispatch($message));
    }

    /**
     * @uses   \Coajaxial\MessagingMediator\Testing\MessageBusStub::activate
     * @covers \Coajaxial\MessagingMediator\Testing\MessageBusStub::dispatch
     */
    public function test_it_will_not_invoke_stub_that_does_not_match(): void
    {
        $message = new MessageBusStubTest_MessageA();

        $stub = $this->createMock(HandlerStub::class);
        $stub->method('matches')
            ->with(self::identicalTo($message))
            ->willReturn(false);

        $stub->expects(self::never())
            ->method('invoke');

        $this->SUT->activate($stub);

        $this->SUT->dispatch($message);
    }

    /**
     * @uses   \Coajaxial\MessagingMediator\Testing\MessageBusStub::activate()
     * @uses   \Coajaxial\MessagingMediator\Testing\MessageBusStub::popUnhandledMessage()
     * @covers \Coajaxial\MessagingMediator\Testing\MessageBusStub::dispatch
     */
    public function test_it_will_save_message_as_unhandled_if_no_stub_matches(): void
    {
        $message = new MessageBusStubTest_MessageA();

        $stub = $this->createMock(HandlerStub::class);
        $stub->method('matches')
            ->with(self::identicalTo($message))
            ->willReturn(false);

        $stub->expects(self::never())
            ->method('invoke');

        $this->SUT->activate($stub);

        $this->SUT->dispatch($message);

        try {
            $this->SUT->popUnhandledMessage();
        } catch (UnderflowException $e) {
            $this->fail('Expected an unhandled message, but there is none.');
        }
    }

    /**
     * @uses   \Coajaxial\MessagingMediator\Testing\MessageBusStub::activate()
     * @uses   \Coajaxial\MessagingMediator\Testing\MessageBusStub::popUnhandledMessage()
     * @covers \Coajaxial\MessagingMediator\Testing\MessageBusStub::dispatch
     */
    public function test_it_will_throw_when_there_are_multiple_stubs_matching_the_same_message(): void
    {
        $message = new MessageBusStubTest_MessageA();

        $stubA = $this->createMock(HandlerStub::class);
        $stubA->method('matches')
            ->with(self::identicalTo($message))
            ->willReturn(true);

        $stubB = $this->createMock(HandlerStub::class);
        $stubB->method('matches')
            ->with(self::identicalTo($message))
            ->willReturn(true);

        $this->SUT->activate($stubA);
        $this->SUT->activate($stubB);

        $this->expectException(MultipleHandlerStubsMatchTheSameMessage::class);
        $this->SUT->dispatch($message);
    }

    /**
     * @covers \Coajaxial\MessagingMediator\Testing\MessageBusStub::clearUnhandledMessages
     * @uses   \Coajaxial\MessagingMediator\Testing\MessageBusStub::dispatch
     * @uses   \Coajaxial\MessagingMediator\Testing\MessageBusStub::popUnhandledMessage
     */
    public function test_it_can_clear_all_unhandled_messages(): void
    {
        $this->SUT->dispatch(new stdClass());
        $this->SUT->dispatch(new stdClass());

        $this->SUT->clearUnhandledMessages();

        $this->expectException(UnderflowException::class);
        $this->SUT->popUnhandledMessage();
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
