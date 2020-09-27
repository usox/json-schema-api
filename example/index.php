<?php

declare(strict_types=1);

use Laminas\Diactoros\ResponseFactory;
use Laminas\Diactoros\StreamFactory;
use Usox\JsonSchemaApi\Contract\ApiMethodInterface;
use Usox\JsonSchemaApi\Endpoint;
use Psr\Container\ContainerInterface;

require_once __DIR__ . '/../vendor/autoload.php';

$request = Laminas\Diactoros\ServerRequestFactory::fromGlobals(
    $_SERVER,
    $_GET,
    $_POST,
    $_COOKIE,
    $_FILES
);

final class BeerlistMethod implements ApiMethodInterface
{
    public function handle(stdClass $parameter): array
    {
        return [
            'styles' => [
                'ipa',
                'lager',
                'porter',
                'stout',
                'quadruple',
            ]
        ];
    }

    public function getSchemaFile(): string
    {
        return __DIR__ . '/schema/beerlist.json';
    }
}
$methodContainer = new class implements ContainerInterface {

    private array $methods;

    public function __construct()
    {
        $this->methods = [
            'beerlist' => new BeerlistMethod(),
        ];
    }

    public function get($id)
    {
        return $this->methods[$id];
    }

    public function has($id)
    {
        return array_key_exists($id, $this->methods);
    }
};

$endpoint = Endpoint::factory(
    new StreamFactory(),
    $methodContainer
);
$response = $endpoint->serve(
    $request,
    (new ResponseFactory())->createResponse()
);

$statusLine = sprintf(
    'HTTP/%s %s %s',
    $response->getProtocolVersion(),
    $response->getStatusCode(),
    $response->getReasonPhrase()
);
header($statusLine);

foreach ($response->getHeaders() as $name => $values) {
    $responseHeader = sprintf(
        '%s: %s',
        $name,
        $response->getHeaderLine($name)
    );
    header($responseHeader);
}

echo $response->getBody();
