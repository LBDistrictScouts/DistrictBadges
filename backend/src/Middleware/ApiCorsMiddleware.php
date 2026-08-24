<?php
declare(strict_types=1);

namespace App\Middleware;

use Cake\Core\Configure;
use Cake\Http\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ApiCorsMiddleware implements MiddlewareInterface
{
    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request Request.
     * @param \Psr\Http\Server\RequestHandlerInterface $handler Request handler.
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $origin = $request->getHeaderLine('Origin');
        $allowedOrigins = (array)Configure::read('Webstore.allowedOrigins', []);
        $isApiRequest = str_starts_with($request->getUri()->getPath(), '/api/');
        $originAllowed = $origin !== '' && in_array($origin, $allowedOrigins, true);

        if ($isApiRequest && $request->getMethod() === 'OPTIONS') {
            $response = new Response(['status' => 204]);
        } else {
            $response = $handler->handle($request);
        }

        if (!$isApiRequest || !$originAllowed) {
            return $response;
        }

        return $response
            ->withHeader('Access-Control-Allow-Origin', $origin)
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
            ->withHeader('Access-Control-Allow-Headers', 'Content-Type')
            ->withHeader('Vary', 'Origin');
    }
}
