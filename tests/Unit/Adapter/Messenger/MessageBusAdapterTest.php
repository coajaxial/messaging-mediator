<?php

namespace Coajaxial\MessagingMediator\Test\Unit\Adapter\Messenger;

use Coajaxial\MessagingMediator\Adapter\Messenger\MessageBusAdapter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

/**
 * @covers \Coajaxial\MessagingMediator\Adapter\Messenger\MessageBusAdapter::__construct
 */
class MessageBusAdapterTest extends TestCase
{
    /**
     * @var MessageBusAdapter
     */
    private $SUT;

    /**
     * @var MockObject&MessageBusInterface
     */
    private $messageBus;

    /**
     * @covers \Coajaxial\MessagingMediator\Adapter\Messenger\MessageBusAdapter::dispatch
     */
    public function test_it_dispatches_message(): void
    {
        $message = new stdClass();

        $this->messageBus
            ->expects($this->once())
            ->method('dispatch')
            ->with(self::identicalTo($message))
            ->willReturn(new Envelope($message));

        $this->SUT->dispatch($message);
    }

    /**
     * @covers \Coajaxial\MessagingMediator\Adapter\Messenger\MessageBusAdapter::dispatch
     */
    public function test_it_will_return_result_of_handled_stamp_if_any(): void
    {
        $message = new stdClass();

        $handledStamp = new HandledStamp(42, 'hans');

        $this->messageBus
            ->expects($this->once())
            ->method('dispatch')
            ->with(self::identicalTo($message))
            ->willReturn(new Envelope($message, [$handledStamp]));

        /** @var int $result */
        $result = $this->SUT->dispatch($message);

        self::assertEquals(42, $result);
    }

    protected function setUp(): void
    {
        $this->messageBus = $this->createMock(MessageBusInterface::class);
        $this->SUT        = new MessageBusAdapter($this->messageBus);
    }
}
