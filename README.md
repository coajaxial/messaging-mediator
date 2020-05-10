# Messaging Mediator

Send messages directly from your domain model without any dependencies

## How it works

Example of a simple message:
```php
final class PostDrafted 
{

}
```

Example of a simple aggregate:
```php
<?php

final class Post 
{
    public static function draft(): Generator 
    {
        // Issue a query - and get its result, wow!
        $isNightTime = yield new IsNightTime();
        assert(is_bool($isNightTime)); // Checking th result is recommended, but optional

        if ( !$isNightTime ) {
            throw new DomainException('Posts can only be drafted at night time!');
        }
  
        // Publish events without any dependency to the event bus!
        yield new PostDrafted();

        // Publish multiple events, yay!
        yield new SomeOtherEvent();
        
        // You can send commands too, but I don't see a use for this tbh
        yield new DoSomething();
    
        // Return construction values, calculated values, etc. if needed.
        return new self();
    }
}
```

## Contribute

### Build docker image

```shell script
docker build -t coajaxial/messaging-mediator .
```

### Load shell aliases

There is a shell aliases file that you can `source`
to import some useful aliases, e.g. `composer`
running from within the docker container 
(coajaxial/messaging-mediator)

```shell script
source .aliases
```