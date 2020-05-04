<?php

namespace Coajaxial\MessagingMediator\Testing;

use RuntimeException;

final class UnhandledMessagesLeft extends RuntimeException
{
    public static function messages(...$messages): self
    {
        return new self(
            sprintf(
                'There are/is %d message(s) left:'.PHP_EOL.PHP_EOL.'%s',
                count($messages),
                print_r($messages, true)
            )
        );
    }
}
