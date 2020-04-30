<?php

namespace Coajaxial\MessagingMediator;

use Generator;
use Throwable;

final class MessagingMediator
{
    /** @var MessageBus */
    private $messageBus;

    public function __construct(MessageBus $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    /** @return mixed */
    public function mediate(Generator $ctx)
    {
        while ($ctx->valid()) {
            $message = $ctx->current();
            try {
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
