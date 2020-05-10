<?php

namespace Coajaxial\MessagingMediator\Adapter\Messenger;

use Coajaxial\MessagingMediator\MessagingMediatorInterface;
use Generator;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

final class MessagingMediatorMiddleware implements MiddlewareInterface
{
    /** @var MessagingMediatorInterface */
    private $mediator;

    public function __construct(MessagingMediatorInterface $mediator)
    {
        $this->mediator = $mediator;
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $envelope = $stack->next()->handle($envelope, $stack);

        /** @var HandledStamp|null $handledStamp */
        $handledStamp = $envelope->last(HandledStamp::class);

        if ($handledStamp === null) {
            return $envelope;
        }

        /** @var Generator<object>|mixed|null $ctx */
        $ctx = $handledStamp->getResult();

        if (!$ctx instanceof Generator) {
            return $envelope;
        }

        /**
         * @psalm-suppress MixedArgumentTypeCoercion
         * @var mixed|null $result
         */
        $result = $this->mediator->mediate($ctx);

        return $envelope->with(new HandledStamp($result, $handledStamp->getHandlerName()));
    }
}
