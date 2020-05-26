<?php

namespace Coajaxial\MessagingMediator\Testing;

use Coajaxial\MessagingMediator\MessageBus;
use SplQueue;
use UnderflowException;

final class MessageBusStub implements MessageBus
{
    /** @var SplQueue<object> */
    private $unhandledMessages;

    /** @var HandlerStub[] */
    private $stubs;

    public function __construct()
    {
        /** @psalm-suppress MixedPropertyTypeCoercion */
        $this->unhandledMessages = new SplQueue();
        $this->stubs             = [];
    }

    public function dispatch(object $message)
    {
        $matchingStub = null;

        foreach ($this->stubs as $stub) {
            if (!$stub->matches($message)) {
                continue;
            }

            if ($matchingStub !== null) {
                throw new MultipleHandlerStubsMatchTheSameMessage();
            }

            $matchingStub = $stub;
        }

        if ($matchingStub !== null) {
            return $matchingStub->invoke($message);
        }

        $this->unhandledMessages->enqueue($message);;

        return null;
    }

    public function popUnhandledMessage(): object
    {
        if ($this->unhandledMessages->isEmpty()) {
            throw new UnderflowException('There are no more unhandled messages left.');
        }

        return $this->unhandledMessages->dequeue();
    }

    public function clearUnhandledMessages(): void
    {
        /** @psalm-suppress MixedPropertyTypeCoercion */
        $this->unhandledMessages = new SplQueue();
    }

    public function ensureNoUnhandledMessagesLeft(): void
    {
        if ($this->unhandledMessages->isEmpty()) {
            return;
        }
        throw UnhandledMessagesLeft::messages(...$this->unhandledMessages);
    }

    public function activate(HandlerStub $stub): void
    {
        $this->stubs[] = $stub;
    }
}
