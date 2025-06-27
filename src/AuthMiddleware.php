<?php
namespace App;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Psr\Http\Server\MiddlewareInterface;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthMiddleware implements MiddlewareInterface
{
    public function process(Request $request, Handler $handler): Response
    {
        $authHeader = $request->getHeaderLine('Authorization');

        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            $response = new \Slim\Psr7\Response();
            $response->getBody()->write(json_encode(['error' => 'Missing or invalid token']));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        $token = str_replace('Bearer ', '', $authHeader);

        try {
            $decoded = JWT::decode($token, new Key($_ENV['JWT_SECRET'], 'HS256'));
            $request = $request->withAttribute('user', (array)$decoded);
            return $handler->handle($request);
        } catch (\Exception $e) {
            $response = new \Slim\Psr7\Response();
            $response->getBody()->write(json_encode(['error' => 'Token invalid', 'message' => $e->getMessage()]));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
    }
}
