<?php

namespace Coajaxial\MessagingMediator\Test\Unit;

use Coajaxial\MessagingMediator\MessageBus;
use Coajaxial\MessagingMediator\MessagingMediator;
use Generator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Throwable;

/**
 * @covers \Coajaxial\MessagingMediator\MessagingMediator::__construct
 * @covers \Coajaxial\MessagingMediator\MessagingMediator::mediate
 */
class MessagingMediatorTest extends TestCase
{
    /**
     * @var MessagingMediator
     */
    private $SUT;

    /**
     * @var MessageBus&MockObject
     */
    private $messageBus;

    public function test_it_returns_return_value_of_context(): void
    {
        $ctx = (static function (): Generator {
            yield from [];

            return 42;
        })();

        $result = $this->SUT->mediate($ctx);

        self::assertEquals(42, $result);
    }

    public function test_it_dispatches_yielded_messages_on_bus(): void
    {
        /** @psalm-var Generator<object> $ctx */
        $ctx = (static function (): Generator {
            yield new MessagingMediatorTest_MessageA();
        })();

        $this->messageBus->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(MessagingMediatorTest_MessageA::class));

        $this->SUT->mediate($ctx);
    }

    public function test_it_sends_result_of_dispatch_back_to_context(): void
    {
        $actualResult   = null;
        $expectedResult = 42;

        /** @psalm-var Generator<object> $ctx */
        $ctx = (static function () use (&$actualResult): Generator {
            /** @var int $actualResult */
            $actualResult = yield new MessagingMediatorTest_MessageA();
        })();

        $this->messageBus
            ->method('dispatch')
            ->with(self::isInstanceOf(MessagingMediatorTest_MessageA::class))
            ->willReturn($expectedResult);

        $this->SUT->mediate($ctx);

        self::assertEquals($expectedResult, $actualResult);
    }

    public function test_nested_contexts(): void
    {
        /** @psalm-var Generator<object> $increasedAnswerToLiveCtx */
        $increasedAnswerToLiveCtx = (static function () {
            /** @var int $answerToLive */
            $answerToLive = yield new MessagingMediatorTest_MessageB();

            try {
                yield new MessagingMediatorTest_MessageC();
            } catch (RuntimeException $e) {
            }

            return $answerToLive + 1;
        })();

        /** @psalm-var Generator<mixed, object, mixed, int> $mainContext */
        $mainContext = (static function () use (&$increasedAnswerToLiveCtx) {
            yield new MessagingMediatorTest_MessageA();
            /** @var int $increasedAnswerToLive */
            $increasedAnswerToLive = yield from $increasedAnswerToLiveCtx;
            yield new MessagingMediatorTest_MessageD();

            return $increasedAnswerToLive;
        })();

        $this->messageBus
            ->expects($this->exactly(4))
            ->method('dispatch')
            ->withConsecutive(
                [self::isInstanceOf(MessagingMediatorTest_MessageA::class)],
                [self::isInstanceOf(MessagingMediatorTest_MessageB::class)],
                [self::isInstanceOf(MessagingMediatorTest_MessageC::class)],
                [self::isInstanceOf(MessagingMediatorTest_MessageD::class)],
            )
            ->will(
                self::onConsecutiveCalls(
                    null,
                    self::returnValue(42),
                    self::throwException(new RuntimeException('This should not happen.')),
                    null
                )
            );

        $increasedAnswerToLive = $this->SUT->mediate($mainContext);

        self::assertEquals(43, $increasedAnswerToLive);
    }

    public function test_it_will_throw_exception_in_context_when_message_dispatch_throws(): void
    {
        /** @psalm-var Generator<object> $ctx */
        $ctx = (static function (): Generator {
            try {
                yield new MessagingMediatorTest_MessageA();
                self::fail('Expected an exception to be thrown.');
            } catch (RuntimeException $e) {
                self::assertEquals('Something went wrong.', $e->getMessage());
            }
        })();

        $this->messageBus
            ->method('dispatch')
            ->with(self::isInstanceOf(MessagingMediatorTest_MessageA::class))
            ->willThrowException(new RuntimeException('Something went wrong.'));

        $this->SUT->mediate($ctx);
    }

    public function test_it_will_continue_normally_when_exception_is_catched_in_context(): void
    {
        /** @psalm-var Generator<object> $ctx */
        $ctx = (static function (): Generator {
            try {
                yield new MessagingMediatorTest_MessageA();
                self::fail('Expected an exception to be thrown.');
            } catch (Throwable $e) {
            }
            yield new MessagingMediatorTest_MessageB();
        })();

        $this->messageBus
            ->expects(self::exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                [self::isInstanceOf(MessagingMediatorTest_MessageA::class)],
                [self::isInstanceOf(MessagingMediatorTest_MessageB::class)]
            )
            ->will(
                self::onConsecutiveCalls(
                    self::throwException(new RuntimeException('Something went wrong.')),
                    null
                )
            );

        $this->SUT->mediate($ctx);
    }

    protected function setUp(): void
    {
        $this->messageBus = $this->createMock(MessageBus::class);
        $this->SUT        = new MessagingMediator($this->messageBus);
    }
}

class MessagingMediatorTest_MessageA
{
}

class MessagingMediatorTest_MessageB
{
}

class MessagingMediatorTest_MessageC
{
}

class MessagingMediatorTest_MessageD
{
}
