<?php

namespace Coajaxial\MessagingMediator\Testing;

use Coajaxial\MessagingMediator\MessageBus;
use SplQueue;

final class MessageBusStub implements MessageBus
{
    /** @var SplQueue */
    private $unhandledMessages;

    public function __construct()
    {
        $this->unhandledMessages = new SplQueue();
    }

    public function dispatch(object $message)
    {
        $this->unhandledMessages->enqueue($message);;

        return null;
    }

    public function ensureNoUnhandledMessagesLeft(): void
    {
        if ($this->unhandledMessages->isEmpty()) {
            return;
        }
        throw UnhandledMessagesLeft::messages(...$this->unhandledMessages);
    }
}
