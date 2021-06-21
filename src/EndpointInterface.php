<?php

namespace Usox\JsonSchemaApi;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;

interface EndpointInterface extends
    MiddlewareInterface
{
    public function serve(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface;
}