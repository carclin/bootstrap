<?php namespace CarClin\Common;

require(__DIR__ . '/../../../../config/db.php');

class Db
{
	private static ?\PDO $pdo = null;

	public static function getPdo(): \PDO
	{
		if (self::$pdo === null) {
			self::$pdo = new \PDO('mysql:host=' . DB_HOST . ':' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8', DB_USER, DB_PASS, [
				\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
				\PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
				\PDO::ATTR_EMULATE_PREPARES => false,
				\PDO::ATTR_STRINGIFY_FETCHES => false,
				\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
			]);
		}

		return self::$pdo;
	}
}
