<?php

namespace Coajaxial\MessagingMediator;

use Generator;
use Throwable;

final class MessagingMediator implements MessagingMediatorInterface
{
    /** @var MessageBus */
    private $messageBus;

    public function __construct(MessageBus $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    public function mediate(Generator $ctx)
    {
        while ($ctx->valid()) {
            /** @var object|mixed $message */
            $message = $ctx->current();
            if (!is_object($message)) {
                throw IncomprehensibleMessage::notAnObject($message);
            }
            try {
                /** @psalm-suppress MixedAssignment */
                $result = $this->messageBus->dispatch($message);
            } catch (Throwable $e) {
                $ctx->throw($e);
                continue;
            }
            $ctx->send($result);
        }

        return $ctx->getReturn();
    }
}
