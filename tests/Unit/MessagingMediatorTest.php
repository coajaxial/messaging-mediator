<?php

namespace Coajaxial\MessagingMediator\Test\Unit;

use Coajaxial\MessagingMediator\MessageBus;
use Coajaxial\MessagingMediator\MessagingMediator;
use DomainException;
use Generator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;
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
     * @var MessageBus|MockObject
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
        $message = new stdClass();

        $ctx = (static function () use ($message): Generator {
            yield $message;
        })();

        $this->messageBus->expects(self::once())
            ->method('dispatch')
            ->with(self::identicalTo($message));

        $this->SUT->mediate($ctx);
    }

    public function test_it_sends_result_of_dispatch_back_to_context(): void
    {
        $message        = new stdClass();
        $actualResult   = null;
        $expectedResult = 42;

        $ctx = (static function () use ($message, &$actualResult): Generator {
            $actualResult = yield $message;
        })();

        $this->messageBus
            ->method('dispatch')
            ->with(self::identicalTo($message))
            ->willReturn($expectedResult);

        $this->SUT->mediate($ctx);

        self::assertEquals($expectedResult, $actualResult);
    }

    public function test_nested_contexts(): void
    {
        $message1 = new stdClass();
        $message2 = new stdClass();
        $message3 = new stdClass();
        $message4 = new stdClass();

        $increasedAnswerToLiveCtx = (static function () use ($message2, $message3) {
            $answerToLive = yield $message2;

            try {
                yield $message3;
            } catch (DomainException $e) {
            }

            return $answerToLive + 1;
        })();

        $mainContext = (static function () use (&$increasedAnswerToLiveCtx, $message1, $message4) {
            yield $message1;
            $increasedAnswerToLive = yield from $increasedAnswerToLiveCtx;
            yield $message4;

            return $increasedAnswerToLive;
        })();

        $this->messageBus
            ->expects($this->exactly(4))
            ->method('dispatch')
            ->withConsecutive(
                [self::identicalTo($message1)],
                [self::identicalTo($message2)],
                [self::identicalTo($message3)],
                [self::identicalTo($message4)],
            )
            ->will(
                self::onConsecutiveCalls(
                    null,
                    self::returnValue(42),
                    self::throwException(new DomainException('This should not happen.')),
                    null
                )
            );

        $increasedAnswerToLive = $this->SUT->mediate($mainContext);

        self::assertEquals(43, $increasedAnswerToLive);
    }

    public function test_it_will_throw_exception_in_context_when_message_dispatch_throws(): void
    {
        $message           = new stdClass();
        $expectedException = new RuntimeException('Something went wrong.');

        $ctx = (static function () use ($message, $expectedException): Generator {
            try {
                yield $message;
                self::fail('Expected an exception to be thrown.');
            } catch (Throwable $e) {
                self::assertSame($expectedException, $e);
            }
        })();

        $this->messageBus
            ->method('dispatch')
            ->with(self::identicalTo($message))
            ->willThrowException($expectedException);

        $this->SUT->mediate($ctx);
    }

    public function test_it_will_continue_normally_when_exception_is_catched_in_context(): void
    {
        $message        = new stdClass();
        $anotherMessage = new stdClass();

        $ctx = (static function () use ($message, $anotherMessage): Generator {
            try {
                yield $message;
                self::fail('Expected an exception to be thrown.');
            } catch (Throwable $e) {
            }
            yield $anotherMessage;
        })();

        $this->messageBus
            ->expects(self::exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                [self::identicalTo($message)],
                [self::identicalTo($anotherMessage)]
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
