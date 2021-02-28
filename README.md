[![Unittests](https://github.com/usox/json-schema-api/actions/workflows/php.yml/badge.svg)](https://github.com/usox/json-schema-api/actions/workflows/php.yml)

# JsonSchemaApi

This library provides a simple way to create a json api using [json-schema](http://json-schema.org/) to validate the request.
You can leverage most of the input validation tasks (variables types, length/content-constraints, lists containing just certain items, etc.)
to the json schema validator and work with the data right away.

## Json-schema

Every method needs a corresponding schema-file which describes, how the request data should like alike.
You can find a simple example in the `example/schema`-folder.

Every request has also to follow a basic schema (see `dist/request.json`) which contains informations about the method and the methods version.

## Requirements

This library requires existing psr7 and psr17 implementations.

## Install

```
composer require usox/json-schema-api
```

## Usage

Get your psr7/17 implementations and youe method-provider (see below) ready and just call the `factory`-method on the endpoint to retrieve an working instance:

```php
$endpoint = \Usox\JsonSchemaApi\Endpoint::factory(
    $psr17StreamFactory,
    $methodProvider
);
$endpoint->serve(
    $psr7Request,
    $psr7Response
);
```

#### Optional: PSR15 Middleware

The endpoint class implements the PSR15 `MiddlewareInterface`.

### MethodProvider

First, a `MethodProvider` needs to be defined. This class is the source for all methods which should be available in the api - 
this could be a simple array, a DI-Container, etc. The class simply has to implement `Usox\JsonSchemApi\Contract\MethodProviderInterface`.

```php
class MyMethodProvider implements \Usox\JsonSchemaApi\Contract\MethodProviderInterface
{
    private array $methodList = ['beerlist' => BeerlistMethod::class];

    public function lookup(string $methodName) : ?\Usox\JsonSchemaApi\Contract\ApiMethodInterface {
        $handler = $this->methodList[$methodName] ?? null;
        if ($handler === null) {
            return null;
        }
        return new $handler;
    }
}
```

### API-Method

The `lookup`-method in the MethodProvider has to return an instance of `Usox\JsonSchemaApi\Contract\ApiMethodInterface`.

Every api method handler has to define two methods:
- The `handle` method which processes the request and returns the result
- The `getSchemaFile` method which returns the path to the schema file which is used to validate the request

```php
class BeerlistMethod implements \Usox\JsonSchemaApi\Contract\ApiMethodInterface
{
    public function handle(stdClass $parameter) : array{
        return ['ipa', 'lager', 'weizen'];
    }
    
    public function getSchemaFile() : string{
        return '/path/to/schema.json';
    }
}
```

## Example
You can find a working example in the `example`-folder.

Just cd to the example-folder and fire up the the php internal webserver `php -S localhost:8888`.
Now you can send `POST`-Requests to the api like this curl-request.

```shell script
curl -X POST -d '{"method": "beerlist", "version": null, "parameter": {"test1": "foobar", "test2": 666}}' "http://localhost:8888"
```
