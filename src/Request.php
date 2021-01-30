<?php namespace Travio\Common;

use Proyect\Root\Root;

class Request
{
	/** @var string|null */
	private static ?string $gatewayKey = null;

	/**
	 * @param string $url
	 * @param mixed $payload
	 * @param array $options
	 * @return mixed
	 */
	public static function make(string $url, $payload = null, array $options = [])
	{
		$options = array_merge([
			'headers' => [],
			'method' => 'GET',
			'json-request' => true,
			'json-response' => true,
		], $options);

		if ($options['method'] === 'HEAD' and $options['json-response'])
			$options['json-response'] = false;

		try {
			$exploded_url = explode('?', $url);
			if (isset($_GET['secret'])) {
				if (isset($exploded_url[1]))
					parse_str($exploded_url[1], $queryString);
				else
					$queryString = [];
				$queryString['secret'] = $_GET['secret'];
				$url = $exploded_url[0] . '?' . http_build_query($queryString);
			}

			$c = curl_init($url);

			if ($options['json-request'])
				$options['headers']['Content-Type'] = 'application/json';
			if (defined('CLIENT_ID') and !isset($options['headers']['X-Client-Id']))
				$options['headers']['X-Client-Id'] = CLIENT_ID;
			if (defined('USER_ID') and !isset($options['headers']['X-User-Id']))
				$options['headers']['X-User-Id'] = USER_ID;

			// TODO attivare quando ci sarà l'autenticazione
//			if ((isset($options['headers']['X-Client-Id']) or isset($options['headers']['X-User-Id'])) and $exploded_url[0] !== 'http://gateway-dealer/public')
//				$options['headers']['X-Gateway-Key'] = self::getGatewayKey();

			$headers = [];
			foreach ($options['headers'] as $k => $v)
				$headers[] = $k . ': ' . $v;

			curl_setopt($c, CURLOPT_HTTPHEADER, $headers);

			curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);

			$options['method'] = strtoupper($options['method']);
			if ($options['method'] === 'GET' and $payload !== null)
				$options['method'] = 'POST';

			if ($options['method'] === 'POST')
				curl_setopt($c, CURLOPT_POST, 1);
			elseif ($options['method'] !== 'GET')
				curl_setopt($c, CURLOPT_CUSTOMREQUEST, $options['method']);

			if ($payload !== null) {
				$body = $options['json-request'] ? json_encode($payload) : $payload;
				curl_setopt($c, CURLOPT_POSTFIELDS, $body);
			}

			$data = curl_exec($c);

			if (curl_errno($c))
				throw new \Exception('Errore cURL: ' . curl_error($c));

			$http_code = curl_getinfo($c, CURLINFO_RESPONSE_CODE);

			curl_close($c);

			if ($options['json-response']) {
				$decoded = json_decode($data, true);
				if ($decoded === null)
					throw new \Exception('Errore nella decodifica dei dati (response code ' . $http_code . '): ' . $data);
			} else {
				$decoded = $data;
			}

			if ($http_code !== 200) {
				if (is_array($decoded)) {
					$error = [];
					if (isset($decoded['error']))
						$error[] = $decoded['error'];
					if (isset($decoded['message']))
						$error[] = $decoded['message'];
					throw new \Exception(implode(' - ', $error), $http_code);
				} else {
					throw new \Exception('', $http_code);
				}
			}
		} catch (\Exception $e) {
			throw new \Exception('Errore durante una richiesta a ' . $url . ': ' . $e->getMessage(), $e->getCode());
		}

		return $decoded;
	}

	/**
	 * @param string $url
	 * @param array $options
	 * @return mixed
	 */
	public static function get(string $url, array $options = [])
	{
		return self::make($url, null, $options);
	}

	/**
	 * @param string $url
	 * @param mixed $payload
	 * @param array $options
	 * @return mixed
	 */
	public static function post(string $url, $payload = [], array $options = [])
	{
		$options['method'] = 'POST';
		return self::make($url, $payload, $options);
	}

	/**
	 * @param string $url
	 * @param array $options
	 * @return mixed
	 */
	public static function head(string $url, array $options = [])
	{
		$options['method'] = 'HEAD';
		return self::make($url, null, $options);
	}

	/**
	 * @param string $url
	 * @param mixed $payload
	 * @param array $options
	 * @return mixed
	 */
	public static function put(string $url, $payload = [], array $options = [])
	{
		$options['method'] = 'PUT';
		return self::make($url, $payload, $options);
	}

	/**
	 * @param string $url
	 * @param mixed $payload
	 * @param array $options
	 * @return mixed
	 */
	public static function delete(string $url, $payload = [], array $options = [])
	{
		$options['method'] = 'DELETE';
		return self::make($url, $payload, $options);
	}

	/**
	 * @return string
	 */
	private static function getGatewayKey(): string
	{
		if (self::$gatewayKey === null)
			self::makeGatewayKey();

		return self::$gatewayKey;
	}

	/**
	 * @throws \Exception
	 */
	private static function makeGatewayKey()
	{
		$publicKey = self::getPublicKey();

		$publicGatewayKeyPath = Root::root() . '/gateway-key';
		if (!file_exists($publicGatewayKeyPath))
			throw new \Exception('Non trovo la gateway key, condividerla al service.');

		$publicGatewayKey = file_get_contents($publicGatewayKeyPath);
		openssl_public_encrypt($publicGatewayKey, $crypted, $publicKey, OPENSSL_PKCS1_OAEP_PADDING);

		self::$gatewayKey = base64_encode($crypted);
	}

	/**
	 * @return string
	 */
	public static function getPublicKey(): string
	{
		$publicKeyPath = Root::root() . '/public.pem';
		if (file_exists($publicKeyPath)) {
			$publicKey = file_get_contents($publicKeyPath);
		} else {
			$publicKey = self::get('http://gateway-dealer/public', ['json-response' => false]);
			file_put_contents(Root::root() . '/public.pem', $publicKey);
		}

		return $publicKey;
	}
}
