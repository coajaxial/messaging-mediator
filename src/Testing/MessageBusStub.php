<?php

namespace Coajaxial\MessagingMediator\Testing;

use Coajaxial\MessagingMediator\MessageBus;
use SplQueue;
use UnderflowException;

final class MessageBusStub implements MessageBus
{
    /** @var SplQueue<object> */
    private $unhandledMessages;

    public function __construct()
    {
        /** @psalm-suppress MixedPropertyTypeCoercion */
        $this->unhandledMessages = new SplQueue();
    }

    public function dispatch(object $message)
    {
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

    public function ensureNoUnhandledMessagesLeft(): void
    {
        if ($this->unhandledMessages->isEmpty()) {
            return;
        }
        throw UnhandledMessagesLeft::messages(...$this->unhandledMessages);
    }
}
