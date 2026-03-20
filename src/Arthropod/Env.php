<?php

namespace Swilen\Arthropod;

final class Env
{
	/**
	 * The directory for load .env file.
	 *
	 * @var string
	 */
	private $path;

	/**
	 * The file name.
	 *
	 * @var string
	 */
	private $filename = '.env';

	/**
	 * List of env saved.
	 *
	 * @var string[]
	 */
	private static $envs = [];

	/**
	 * List of all env saved.
	 *
	 * @var string[]
	 */
	private static $store = [];

	/**
	 * @var bool
	 */
	private $isImmutable = true;

	/**
	 * The stack variables resolved.
	 *
	 * @var array
	 */
	private static $stack = [];

	/**
	 * The env instance as singleton.
	 *
	 * @var static
	 */
	private static $instance;

	/**
	 * Create new env instance.
	 *
	 * @param string $path
	 * @param bool   $isImmutable
	 *
	 * @return void
	 */
	public function __construct(?string $path = null, bool $isImmutable = true)
	{
		$this->path = $path;
		$this->isImmutable = $isImmutable;
	}

	/**
	 * Create environment instance from given path.
	 *
	 * @param string $path
	 * @param bool   $isImmutable
	 *
	 * @return $this
	 */
	public static function createFrom(string $path, bool $isImmutable = true)
	{
		return new static($path, $isImmutable);
	}

	/**
	 * Create environment instance from given array.
	 *
	 * @param array<string, mixed> $envs
	 * @param bool                 $isImmutable
	 *
	 * @return $this
	 */
	public static function createFromArray(array $envs, bool $isImmutable = true)
	{
		$instance = new static(null, $isImmutable);
		$instance->loadFromArray($envs);

		return $instance;
	}

	/**
	 * Return full file path of env.
	 *
	 * @return string
	 */
	public function environmentFilePath()
	{
		return $this->path . DIRECTORY_SEPARATOR . $this->filename;
	}

	/**
	 * Return path of en file.
	 *
	 * @return string
	 */
	public function path()
	{
		return $this->path;
	}

	/**
	 * Return the name of env file.
	 *
	 * @return string
	 */
	public function filename()
	{
		return $this->filename;
	}

	/**
	 * Check if enviroment is inmutable.
	 *
	 * @return bool
	 */
	public function isImmutable()
	{
		return (bool) $this->isImmutable;
	}

	/**
	 * Configure the environment manager instance.
	 *
	 * @param array{file?: string, path?: string, immutable?: bool} $config
	 *
	 * @return $this
	 */
	public function config(array $config): self
	{
		$this->filename = $config['file'] ?? $this->filename;
		$this->path = $config['path'] ?? $this->path;
		$this->isImmutable = (bool) ($config['immutable'] ?? $this->isImmutable);

		return $this;
	}

	/**
	 * Load environment variables from a file.
	 *
	 * @throws RuntimeException If the file is missing or outside allowed path.
	 */
	public function load()
	{
		$filePath = $this->environmentFilePath();
		if (!is_readable($filePath)) {
			throw new \RuntimeException("Environment file [{$this->filename}] is not readable at [{$this->path}].");
		}

		static::$instance = $this;
		$lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

		if ($lines === false) {
			throw new \RuntimeException("Environment file [{$this->filename}] is not readable at [{$this->path}].");
		}

		foreach ($lines as $line) {
			$line = trim($line);
			if (!$line || str_starts_with($line, '#')) {
				continue;
			}

			[$key, $value] = str_contains($line, '=') ? explode('=', $line, 2) : [$line, null];
			$this->compile($key, $value);
		}

		return $this;
	}

	/**
	 * Load environment variables directly from an associative array.
	 *
	 * @param array<string, mixed> $data
	 *
	 * @return $this
	 */
	public function loadFromArray(array $data): self
	{
		static::$instance = $this;
		foreach ($data as $key => $value) {
			$this->compile($key, $value);
		}

		return $this;
	}

	/**
	 * Compile variables with variables replaced.
	 *
	 * @param string               $key
	 * @param string|int|bool|null $value
	 * @param bool                 $replace
	 *
	 * @return void
	 */
	public function compile(string $key, $value, bool $replace = false)
	{
		$key = $this->formatKey($key);
		$value = $this->formatValue($value);

		// Store in stack for potential cross-variable resolution
		self::$stack[$key] = $value;

		// Resolve ${VAR} or {VAR} references
		if (is_string($value) && str_contains($value, '{')) {
			$value = preg_replace_callback('/\$?{[A-Z0-9\_]+}/', function ($matches) {
				$key = $this->formatKey($matches[0], '${\}');
				return (static::$stack[$key] ?? $matches[0]);
			}, $value);

			self::$stack[$key] = $value;
		}

		$this->write($key, $value, $replace);
	}

	/**
	 * Format key and replace special characters.
	 *
	 * @param string      $key
	 * @param string|null $replace
	 *
	 * @return string
	 */
	private function formatKey(string $key, ?string $replace = null)
	{
		$key = $replace ? trim($key, $replace) : trim($key);

		return str_replace('-', '_', strtoupper($key));
	}

	/**
	 * Format value and remove comments.
	 *
	 * @param int|string|bool $value
	 *
	 * @return bool|int|string
	 */
	private function formatValue($value)
	{
		// check value is primitive
		if ($value === null || $value === true || $value === false || is_int($value) || is_float($value)) {
			return $value;
		}

		// remove comment
		if (($startComment = strpos($value, '#')) !== false) {
			$value = trim(substr($value, 0, $startComment));
		}

		return $this->parseToPrimitive($value);
	}

	/**
	 * Parse values to php primitives.
	 *
	 * @param string|int|bool $value
	 *
	 * @return bool|int|string
	 */
	private function parseToPrimitive(string $value): mixed
	{
		$len = strlen($value);
		$quoted = false;

		// Handle quoted strings
		if ($len >= 2) {
			$first = $value[0];
			$last  = $value[$len - 1];

			if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
				$value = substr($value, 1, -1);
				$quoted = true;
			}
		}

		$lower = strtolower($value);
		return match ($lower) {
			'true', '(true)', 'on', 'yes' => true,
			'false', '(false)', 'off', 'no' => false,
			'null', '(null)', '' => null,
			default => $this->parseComplexStrings($value, $quoted),
		};
	}

	private function parseComplexStrings(string $value, bool $quoted = false): mixed
	{
		if (!$quoted && (is_numeric($value) || str_starts_with($value, '+') || str_starts_with($value, '-'))) {
			return str_contains($value, '.') ? (float) $value : (int) $value;
		}

		if (str_starts_with($value, 'base64:')) {
			return base64_decode(substr($value, 7));
		}

		if (str_starts_with($value, 'swilen:')) {
			return base64_decode(substr($value, 7) . '=');
		}

		return $value;
	}

	/**
	 * Write value to env collection with mutability checked.
	 *
	 * @param string $key
	 * @param mixed  $value
	 * @param bool   $replace
	 *
	 * @return void
	 */
	private function write(string $key, $value, bool $replace = false)
	{
		if ($replace || !$this->isImmutable() || !$this->exists($key)) {
			static::$envs[$key] = $value;
			$_ENV[$key] = $value;
			$_SERVER[$key] = $value;
			// Clear store cache to force re-merge on next all() call
			static::$store = [];
		}
	}

	/**
	 * Check key exists into enn collection.
	 *
	 * @param string|int $key
	 *
	 * @return bool
	 */
	private function exists($key)
	{
		return key_exists($key, $_SERVER) && key_exists($key, $_ENV) && key_exists($key, static::$envs);
	}

	/**
	 * Get value with keyed from stored env variables.
	 *
	 * @param string     $key
	 * @param mixed|null $default
	 *
	 * @return mixed|null
	 */
	public static function get($key, $default = null)
	{
		$all = static::all();

		return key_exists($key, $all)
			? $all[$key]
			: $default;
	}

	/**
	 * Return all env variables.
	 *
	 * @return array
	 */
	public static function all()
	{
		if (!empty(static::$store)) {
			return static::$store;
		}

		return static::$store = array_merge($_ENV, $_SERVER, static::$envs);
	}

	/**
	 * Return instance for manipule content has singleton.
	 *
	 * @return static|null
	 */
	public static function getInstance()
	{
		return static::$instance;
	}

	/**
	 * Set enviroment in runtime.
	 *
	 * @param string $key
	 * @param mixed  $value
	 *
	 * @return void
	 */
	public static function set($key, $value)
	{
		$instance = static::getInstance();

		$instance->compile($key, $value);
	}

	/**
	 * Set enviroment in runtime.
	 *
	 * @param string $key
	 * @param mixed  $value
	 *
	 * @return void
	 */
	public static function replace($key, $value)
	{
		$instance = static::getInstance();

		$instance->compile($key, $value, true);
	}

	/**
	 * Return array of variables values registered.
	 *
	 * @return array<string, mixed>
	 */
	public static function registered()
	{
		return static::$envs;
	}

	/**
	 * Return stack with variables resolved.
	 *
	 * @return array<string, mixed>
	 */
	public static function stack()
	{
		return static::$stack;
	}

	/**
	 * Forget environement instances and variables stored.
	 *
	 * @return void
	 */
	public static function forget()
	{
		foreach (array_keys(static::$envs) as $key) {
			unset($_ENV[$key], $_SERVER[$key]);
		}
		static::$instance = null;
		static::$envs = static::$store = static::$stack = [];
	}
}
