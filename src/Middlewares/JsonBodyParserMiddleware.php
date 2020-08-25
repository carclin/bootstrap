<?php namespace CarClin\Common\Middlewares;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

class JsonBodyParserMiddleware implements MiddlewareInterface
{
	public function process(Request $request, RequestHandler $handler): Response
	{
		$contentType = $request->getHeaderLine('Content-Type');

		if (strstr($contentType, 'application/json')) {
			$body = file_get_contents('php://input');
			if (empty($body)) {
				$request = $request->withParsedBody([]);
			} else {
				$contents = json_decode($body, true);
				if (json_last_error() === JSON_ERROR_NONE) {
					$request = $request->withParsedBody($contents);
				} else {
					throw new \Exception(json_last_error_msg(), 400);
				}
			}
		}

		return $handler->handle($request);
	}
}
