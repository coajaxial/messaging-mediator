<?php

namespace Coajaxial\MessagingMediator\Test\Unit\Adapter\Messenger;

use Coajaxial\MessagingMediator\Adapter\Messenger\MessagingMediatorMiddleware;
use Coajaxial\MessagingMediator\MessagingMediatorInterface;
use Generator;
use PHPUnit\Framework\MockObject\MockObject;
use stdClass;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Messenger\Test\Middleware\MiddlewareTestCase;

/**
 * @covers \Coajaxial\MessagingMediator\Adapter\Messenger\MessagingMediatorMiddleware::__construct
 */
class MessagingMediatorMiddlewareTest extends MiddlewareTestCase
{
    /**
     * @var MessagingMediatorInterface&MockObject
     */
    private $mediator;

    /**
     * @var MessagingMediatorMiddleware
     */
    private $SUT;

    /**
     * @covers \Coajaxial\MessagingMediator\Adapter\Messenger\MessagingMediatorMiddleware::handle
     */
    public function test_it_will_do_nothing_when_message_has_no_handled_stamp(): void
    {
        $this->mediator
            ->expects($this->never())
            ->method('mediate');

        /** @var StackInterface $stack */
        $stack = $this->getStackMock();
        $this->SUT->handle(new Envelope(new stdClass()), $stack);
    }

    /**
     * @covers \Coajaxial\MessagingMediator\Adapter\Messenger\MessagingMediatorMiddleware::handle
     */
    public function test_it_will_do_nothing_when_result_of_handled_stamp_is_no_generator(): void
    {
        $this->mediator
            ->expects($this->never())
            ->method('mediate');

        $handledStamp = new HandledStamp(42, 'some_handler');

        /** @var StackInterface $stack */
        $stack = $this->getStackMock();
        $this->SUT->handle(new Envelope(new stdClass(), [$handledStamp]), $stack);
    }

    /**
     * @covers \Coajaxial\MessagingMediator\Adapter\Messenger\MessagingMediatorMiddleware::handle
     */
    public function test_it_will_delegate_generator_to_mediator(): void
    {
        $ctx = (static function (): Generator {
            yield 42;
        })();

        $this->mediator
            ->expects($this->once())
            ->method('mediate')
            ->with(self::identicalTo($ctx));

        $handledStamp = new HandledStamp($ctx, 'some_handler');

        /** @var StackInterface $stack */
        $stack = $this->getStackMock();
        $this->SUT->handle(new Envelope(new stdClass(), [$handledStamp]), $stack);
    }

    /**
     * @covers \Coajaxial\MessagingMediator\Adapter\Messenger\MessagingMediatorMiddleware::handle
     */
    public function test_it_will_replace_handled_stamp_result_with_real_result(): void
    {
        $ctx = (static function (): Generator {
            yield 21;
        })();

        $this->mediator
            ->method('mediate')
            ->with(self::identicalTo($ctx))
            ->willReturn(42);

        $handledStamp = new HandledStamp($ctx, 'some_handler');

        /** @var StackInterface $stack */
        $stack    = $this->getStackMock();
        $envelope = $this->SUT->handle(new Envelope(new stdClass(), [$handledStamp]), $stack);

        /** @var HandledStamp|null $handledStamp */
        $handledStamp = $envelope->last(HandledStamp::class);

        self::assertNotNull($handledStamp);
        self::assertEquals(42, $handledStamp->getResult());
    }

    protected function setUp(): void
    {
        $this->mediator = $this->createMock(MessagingMediatorInterface::class);
        $this->SUT      = new MessagingMediatorMiddleware($this->mediator);
    }
}
