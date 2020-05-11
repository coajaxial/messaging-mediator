# Messaging Mediator

![Unit tests](https://github.com/coajaxial/messaging-mediator/workflows/Unit%20tests/badge.svg)
![Integration tests](https://github.com/coajaxial/messaging-mediator/workflows/Integration%20tests/badge.svg)

Send messages directly from your domain model without any dependencies

## The idea behind this project

When I first implemented domain events for my domain model, I stored all events in a collection,
that could be retrieved and cleared. It looked something like this:

```php
class MyAggregate {
    /** @var object[] */
    private $events = [];

    public function doSomething(): void {
        // ...
        $this->events = new SomethingHappend();
    }
    
    /** @return object[] */
    public function getEvents(): array {
        return $this->events;
    }

    public function clearEvents(): void {
        $this->events = [];
    }
}
```

This works quite well, but every aggregate needed this boilerplate, so I had to
put this boilerplate either into a super-`class`, which all of my aggregates extended, or  
into a `trait`.

There are some other techniques to implement domain events, for example you can `return` them, like so:

```php
class MyAggregate {
    public function doSomething(): array {
        // ...
        return [
            new SomethingHappened()
        ];       
    }   
}
```

With this approach you have no more boilerplate, but you also loose the ability to return normal values,
like for example the `self` instance for named constructors, or some calculated values.

So I don't know exactly how I came up with this new idea, but it basically is: `yield`ing the domain events:

```php
class MyAggregate {
    public function doSomething(): Generator {
        // ...
        yield new SomethingHappened();       
    }   
}
```

This has several advantages:

- Absolutely no boilerplate, no need to extend a super-class or use a trait
- You can still return normal values
- You can extend this method to use it for any kind of messaging (more on that later)

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

Example of a command handler:

```php
final class DraftPostHandler {

    public function __invoke(DraftPost $command): Generator
    {
        $post = yield from Post::draft();

        yield from $post->publish();

        // ...
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