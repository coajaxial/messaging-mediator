<?php

namespace Coajaxial\MessagingMediator\Test\Integration\Messenger;

use Coajaxial\MessagingMediator\Adapter\Messenger\MessageBusAdapter;
use Coajaxial\MessagingMediator\Adapter\Messenger\MessagingMediatorMiddleware;
use Coajaxial\MessagingMediator\MessagingMediator;
use Coajaxial\MessagingMediator\Testing\LazyMessageBus;
use Generator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Handler\HandlersLocator;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\Middleware\HandleMessageMiddleware;
use Symfony\Component\Messenger\Stamp\HandledStamp;

final class MessengerTest extends TestCase
{
    /** @var MessageBus */
    private $bus;

    public function test_it(): void
    {
        $result = $this->bus->dispatch(new MessengerTest_MessageA(2));

        /** @var HandledStamp|null $handledStamp */
        $handledStamp = $result->last(HandledStamp::class);

        self::assertNotNull($handledStamp);
        self::assertEquals(2 * 42, $handledStamp->getResult());
    }

    public function handleMultiply(MessengerTest_Multiply $message): int
    {
        return $message->getA() * $message->getB();
    }

    public function handleMessageA(MessengerTest_MessageA $message): Generator
    {
        return yield new MessengerTest_Multiply($message->getA(), 42);
    }

    protected function setUp(): void
    {
        $mediatorBus = new LazyMessageBus();

        $mediator = new MessagingMediator($mediatorBus);

        $handlersLocator = new HandlersLocator(
            [
                MessengerTest_MessageA::class => [[$this, 'handleMessageA']],
                MessengerTest_Multiply::class => [[$this, 'handleMultiply']],
            ]
        );

        $this->bus = new MessageBus(
            [
                new MessagingMediatorMiddleware($mediator),
                new HandleMessageMiddleware($handlersLocator),
            ]
        );

        $mediatorBus->initialize(new MessageBusAdapter($this->bus));
    }
}

class MessengerTest_MessageA
{
    /** @var int */
    private $a;

    public function __construct(int $a)
    {
        $this->a = $a;
    }

    public function getA(): int
    {
        return $this->a;
    }
}

class MessengerTest_Multiply
{
    /** @var int */
    private $a;

    /** @var int */
    private $b;

    public function __construct(int $a, int $b)
    {
        $this->a = $a;
        $this->b = $b;
    }

    public function getA(): int
    {
        return $this->a;
    }

    public function getB(): int
    {
        return $this->b;
    }
}
