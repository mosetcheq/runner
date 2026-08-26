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
	public array $params = [];

	public array $namedParams = [];
	public array $unnamedParams = [];

	public FormHandler $formHandler;

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
	 * Vrátí celý GET jako pole
	 *
	 * @return mixed
	 */
	public function getQueryData() : array
	{
		return $this->get;
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


	/**
	 * Vratí objekt uploadovaného souboru
	 *
	 * @param string $key název formulářového pole
	 * @return UploadedFile|null
	 */
	public function getFile(string $key) : ?UploadedFile
	{
		if(!isset($this->files[$key])) return null;
		if(is_uploaded_file($this->files[$key]['tmp_name']))
		{
			return new UploadedFile($this->files[$key]);
		} else {
			return null;
		}
	}


	/**
	 * Vrátí soubory jako iterátor
	 *
	 * @param string $key název formulářového pole
	 * @return UploadedFileIterator|null
	 */
	public function getFiles(string $key) : ?UploadedFileIterator
	{
		if(!isset($this->files[$key])) return null;
		if($this->files[$key])
		{
			return new UploadedFileIterator($this->files[$key]);
		} else {
			return null;
		}
	}

	public function addNamedParam(string $name, mixed $value): void
    {
        $this->namedParams[$name] = $value;
    }

    public function addUnnamedParam(mixed $value): void
    {
        $this->unnamedParams[] = $value;
    }

    public function getNamedParam(string $name): mixed
    {
        return $this->namedParams[$name] ?? null;
    }

    public function getUnnamedParams(): array
    {
        return $this->unnamedParams;
    }

}



class UploadedFile {

	public readonly string $tmp_name;
	public readonly int $size;
	public readonly string $name;
	public readonly string $type;
	public readonly string $extension;
	public readonly string $shortname;

	public function __construct(?array $file = null) {
		if(!$file) return;
		foreach($file as $key => $value) $this->$key = $value;
		$parts = explode('.', $this->name);
		$this->extension = strtolower(array_pop($parts));
		$this->shortname = implode('.', $parts);
	}


	public function isImage() : bool
	{
		return str_starts_with($this->type, 'image/');
	}

	public function move(string $directory, string $name = '') : bool
	{
		return move_uploaded_file($this->tmp_name, rtrim($directory, '/') . '/' . ($name ? $name : $this->name));
	}
}


class UploadedFileIterator implements \Iterator, \Countable {

	private array $files = [];
	private int $pointer;

	public function __construct(?array $files) {
		if(!$files) return;

		if(is_array($files['tmp_name'])) {
			foreach($files['tmp_name'] as $i => $tmp_name) {
				if(is_uploaded_file($tmp_name)) {
					$this->files[] = new UploadedFile([
						'tmp_name' => $tmp_name,
						'size' => $files['size'][$i],
						'name' => $files['name'][$i],
						'type' => $files['type'][$i]
					]);
				}
			}
		} else {
			if(is_uploaded_file($files['tmp_name'])) {
				$this->files[] = new UploadedFile($files);
			}
		}

		$this->pointer = 0;
	}

	function rewind(): void {
		$this->pointer = 0;
	}

	function current(): UploadedFile {
		return $this->files[$this->pointer];
	}

	function key(): mixed {
		return $this->pointer;
	}

	function next(): void {
		++$this->pointer;
	}

	function valid(): bool {
		return isset($this->files[$this->pointer]);
	}

    function count(): int {
        return count($this->files);
    }
}
