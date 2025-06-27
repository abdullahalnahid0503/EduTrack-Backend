<?php
use Slim\App;
use Slim\Middleware\ErrorMiddleware;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

return function (App $app) {
    // Enable JSON parsing
    $app->addBodyParsingMiddleware();

    // CORS Middleware
    $app->add(function (ServerRequestInterface $request, RequestHandlerInterface $handler) {
        $response = $handler->handle($request);
        $origin = $request->getHeaderLine('Origin') ?: '*';

        return $response
            ->withHeader('Access-Control-Allow-Origin', $origin)
            ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS')
            ->withHeader('Access-Control-Allow-Credentials', 'true');
    });

    // Fix Preflight (OPTIONS) request
    $app->options('/{routes:.+}', function ($request, $response) {
        return $response;
    });

    // Routing middleware
    $app->addRoutingMiddleware();

    // Error handler
    $errorMiddleware = new ErrorMiddleware(
        $app->getCallableResolver(),
        $app->getResponseFactory(),
        true,
        true,
        true
    );
    $app->add($errorMiddleware);
};
