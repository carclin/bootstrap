<?php namespace CarClin\Common;

use CarClin\Common\Middlewares\AuthMiddleware;
use CarClin\Common\Middlewares\JsonBodyParserMiddleware;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Factory\AppFactory;

class CarClin
{
	public static function create(bool $useAuth = true): App
	{
		$app = AppFactory::create();

		$app->addRoutingMiddleware();

		$app->add(new JsonBodyParserMiddleware());
		if ($useAuth)
			$app->add(new AuthMiddleware());

		// Define Custom Error Handler
		$customErrorHandler = function (
			Request $request,
			\Throwable $exception,
			bool $displayErrorDetails,
			bool $logErrors,
			bool $logErrorDetails
		) use ($app) {
			$isSlimHttpException = is_subclass_of($exception, \Slim\Exception\HttpException::class);
			$code = (get_class($exception) === 'Exception' or $isSlimHttpException) ? ($exception->getCode() ?: 500) : 500;

			$message = $exception->getMessage();
			if ($isSlimHttpException and $exception->getDescription())
				$message .= ' - ' . $exception->getDescription();

			$response = $app->getResponseFactory()->createResponse();
			$response->getBody()->write(json_encode([
				'error' => $message,
			], JSON_UNESCAPED_UNICODE));

			return $response->withStatus($code)->withHeader('Content-Type', 'application/json');
		};

		// Add Error Middleware
		$errorMiddleware = $app->addErrorMiddleware(true, true, true);
		$errorMiddleware->setDefaultErrorHandler($customErrorHandler);

		return $app;
	}
}
