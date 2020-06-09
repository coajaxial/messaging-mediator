<?php

namespace Coajaxial\MessagingMediator;

use RuntimeException;

final class IncomprehensibleMessage extends RuntimeException
{
    /** @param mixed $message */
    public static function notAnObject($message): self
    {
        return new self(
            sprintf('Message is not an instance of a class. Message was:'.PHP_EOL.'%s', print_r($message, true))
        );
    }
}
