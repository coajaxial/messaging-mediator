<?php

namespace Coajaxial\MessagingMediator\Testing;

use Coajaxial\MessagingMediator\MessageBus;

/**
 * Provides lazy initialization for a message bus to prevent
 * dependency loops, e.g. Mediator -> MessageBus -> MediatorMiddleware -x-> Mediator.
 */
class LazyMessageBus implements MessageBus
{
    /** @var MessageBus|null */
    private $originalBus;

    public function dispatch(object $message)
    {
        if ($this->originalBus === null) {
            throw new MessageBusUninitialized(
                'Bus is not initialized yet. Use `initialize($bus)` to initialize the bus.'
            );
        }

        return $this->originalBus->dispatch($message);
    }

    public function initialize(MessageBus $bus): void
    {
        $this->originalBus = $bus;
    }
}