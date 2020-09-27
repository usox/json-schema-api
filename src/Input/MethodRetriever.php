<?php

declare(strict_types=1);

namespace Usox\JsonSchemaApi\Input;

use Psr\Container\ContainerInterface;
use stdClass;
use Teapot\StatusCode;
use Usox\JsonSchemaApi\Contract\ApiMethodInterface;
use Usox\JsonSchemaApi\Exception\MethodNotFoundException;
use Usox\JsonSchemaApi\Exception\RequestValidationException;

final class MethodRetriever implements MethodRetrieverInterface
{
    private MethodValidatorInterface $methodValidator;
    
    private ContainerInterface $container;

    public function __construct(
        MethodValidatorInterface $methodValidator,
        ContainerInterface $container
    ) {
        $this->methodValidator = $methodValidator;
        $this->container = $container;
    }

    /**
     * @throws MethodNotFoundException
     * @throws RequestValidationException
     */
    public function retrieve(
        stdClass $input
    ): ApiMethodInterface {
        // Get the method name from the request
        $methodName = $input->method;

        $version = $input->version;
        if ($version !== null) {
            $methodName = sprintf('%s.%d', $methodName, $version);
        }

        // If no handler was found (== method does not exist), bailout
        if ($this->container->has($methodName) === false) {
            throw new MethodNotFoundException(
                'Method not found',
                StatusCode::BAD_REQUEST
            );
        }

        // Search for the handler in the lookup table
        /** @var ApiMethodInterface $handler */
        $handler = $this->container->get($methodName);

        $this->methodValidator->validate($handler, $input);
        
        return $handler;
    }
}