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
        yield new PostDrafted();
    
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