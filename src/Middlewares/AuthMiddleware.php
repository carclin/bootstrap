<?php namespace CarClin\Common\Middlewares;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

class AuthMiddleware implements MiddlewareInterface
{
	public function process(Request $request, RequestHandler $handler): Response
	{
		$user = $request->getHeader('X-User-Id');
		define('USER_ID', $user ? (int)$user[0] : null);
		return $handler->handle($request);
	}
}
