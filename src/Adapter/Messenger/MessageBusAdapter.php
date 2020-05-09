<?php

namespace Coajaxial\MessagingMediator\Adapter\Messenger;

use Coajaxial\MessagingMediator\MessageBus;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

final class MessageBusAdapter implements MessageBus
{
    /** @var MessageBusInterface */
    private $messageBus;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    public function dispatch(object $message)
    {
        $envelope = $this->messageBus->dispatch($message);

        /** @var HandledStamp|null $stamp */
        $stamp = $envelope->last(HandledStamp::class);

        if ($stamp !== null) {
            return $stamp->getResult();
        }

        return null;
    }
}
