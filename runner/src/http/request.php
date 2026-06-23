<?php
namespace Rnr\Http;

/**
 * ENUM pro request body
 */
enum RequestType: string
{
	case Json = 'application/json';
	case XML = 'application/xml';

	/**
	 * Parsuje tělo requestu
	 *
	 * @param string $body
	 * @return mixed
	 */
	public function parse(string $body): mixed
	{
		return match($this) {
			self::Json => json_decode($body, true),
			self::XML => simplexml_load_string($body) ?: null,
		};
	}
}


/**
 * Request Class - třída pro práci s requesty
 */
class Request
{

	private array $server;
	private array $get;
	private array $post;
	private array $cookies;
	private array $files;
	private ?string $body = null;


	public function __construct()
	{
		$this->server = $_SERVER;
		$this->get = $_GET;
		$this->post = $_POST;
		$this->cookies = $_COOKIE;
		$this->files = $_FILES;
		$this->body = file_get_contents('php://input');
	}

	/**
	 * Vrátí typ HTTP metody
	 *
	 * @return string
	 */
	public function getMethod() : string
	{
		return strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');
	}


	/**
	 * Vrátí URI stránky
	 *
	 * @return string
	 */
	public function getUri() : string
	{
		return $this->server['REQUEST_URI'] ?? '/';
	}


	/**
	 * Vrátí hodnotu z GET / Query string
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function getQuery(string $key) : mixed
	{
		return $this->get[$key] ?? null;
	}


	/**
	 * Vrátí hodnotu z POST pole
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function getPost(string $key) : mixed
	{
		return $this->post[$key] ?? null;
	}


	/**
	 * Vrátí celý POST block jako pole
	 *
	 * @return array
	 */
	public function getPostData() : array {
		return $this->post;
	}


	/**
	 * Vrátí Path
	 *
	 * @return string
	 */
	public function getPath(): string
	{
		return parse_url($this->getUri(), PHP_URL_PATH) ?? '/';
	}


	/**
	 * Vrátí Content Type requestu
	 *
	 * @return string|null
	 */
	public function getContentType() : ?string
	{
		return $this->server['CONTENT_TYPE'] ?? null;
	}


	/**
	 * Vrátí HTTP referer pokud je nastaven
	 *
	 * @return string|null
	 */
	public function getReferer() : ?string
	{
		return $this->server['HTTP_REFERER'] ?? null;
	}


	/**
	 * Vrátí hodnotu HTTP Headeru
	 *
	 * @param string $name Název hlavičky
	 * @return string|null
	 */
	public function getHeader(string $name) : ?string
	{
		$key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
		return $this->server[$key] ?? null;
	}


	/**
	 * Vrací TRUE pokud se jedná o request z Ajaxu
	 *
	 * @return boolean
	 */
	public function isAjax() : bool
	{
		return $this->getHeader('X-Requested-With') === 'XMLHttpRequest';
	}


	/**
	 * Vrací RAW POST BODY
	 *
	 * @return string|null
	 */
	public function getBody() : ?string
	{
		return $this->body;
	}


	/**
	 * Vrací TRUE pokud se jedná o request přes HTTPS
	 *
	 * @return boolean
	 */
	public function isSecure(): bool
	{
		return (!empty($this->server['HTTPS']) && $this->server['HTTPS'] !== 'off')	|| ($this->server['SERVER_PORT'] ?? 80) == 443;
	}


	/**
	 * Vrátí IP adresu klienta
	 *
	 * @return string
	 */
	public function getIp(): string
	{
		$nginxIp = $this->getHeader('X-Real-IP');
		if(!$nginxIp)
		{
			return $this->server['REMOTE_ADDR'] ?? '0.0.0.0';
		} else {
			return $nginxIp;
		}
	}


	/**
	 * Vrátí ENUM request type
	 *
	 * @return RequestType|null
	 */
	public function getRequestType() : ?RequestType
	{
		$contentType = $this->getContentType();
		if(!$contentType) {
			return null;
		}

		foreach(RequestType::cases() as $case) {
			if(str_starts_with($contentType, $case->value)) {
				return $case;
			}
		}

		return null;
	}


	/**
	 * Vrátí parsované tělo requestu
	 *
	 * @return mixed
	 */
	public function getParsedBody() : mixed
	{
		$type = $this->getRequestType();
		return $type?->parse($this->body ?? '');
	}


	/**
	 * Vrátí hodnotu Cookie
	 *
	 * @param string $key
	 * @return string|null
	 */
	public function getCookie(string $key) : ?string
	{
		return $this->cookies[$key] ?? null;
	}


	/**
	 * Vrací celý blok cookies jako pole
	 *
	 * @return array
	 */
	public function getCookies(): array
	{
		return $this->cookies;
	}

}

