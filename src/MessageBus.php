<?php

namespace Coajaxial\MessagingMediator;

interface MessageBus
{
    /** @return mixed */
    public function dispatch(object $message);
}
