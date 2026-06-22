<?php
namespace Rnr\Http;

enum ResponseType
{
    case Template;
    case Json;
    case File;
    case Redirect;
    case Previous;
    case Empty;
    case Plain;
}

enum HttpStatus: int
{
    // 2xx Success
    case OK = 200;
    case Created = 201;
    case Accepted = 202;
    case NoContent = 204;

    // 3xx Redirects
    case MovedPermanently = 301;
    case Found = 302;
    case SeeOther = 303;
    case NotModified = 304;
    case TemporaryRedirect = 307;
    case PermanentRedirect = 308;

    // 4xx Client errors
    case BadRequest = 400;
    case Unauthorized = 401;
    case Forbidden = 403;
    case NotFound = 404;
    case MethodNotAllowed = 405;
    case Conflict = 409;
    case Gone = 410;
    case UnprocessableEntity = 422;

    // 5xx Server errors
    case InternalServerError = 500;
    case NotImplemented = 501;
    case BadGateway = 502;
    case ServiceUnavailable = 503;

    public function message(): string
    {
        return match($this) {
            // 2xx
            self::OK => 'OK',
            self::Created => 'Created',
            self::Accepted => 'Accepted',
            self::NoContent => 'No Content',

            // 3xx
            self::MovedPermanently => 'Moved Permanently',
            self::Found => 'Found',
            self::SeeOther => 'See Other',
            self::NotModified => 'Not Modified',
            self::TemporaryRedirect => 'Temporary Redirect',
            self::PermanentRedirect => 'Permanent Redirect',

            // 4xx
            self::BadRequest => 'Bad Request',
            self::Unauthorized => 'Unauthorized',
            self::Forbidden => 'Forbidden',
            self::NotFound => 'Not Found',
            self::MethodNotAllowed => 'Method Not Allowed',
            self::Conflict => 'Conflict',
            self::Gone => 'Gone',
            self::UnprocessableEntity => 'Unprocessable Entity',

            // 5xx
            self::InternalServerError => 'Internal Server Error',
            self::NotImplemented => 'Not Implemented',
            self::BadGateway => 'Bad Gateway',
            self::ServiceUnavailable => 'Service Unavailable',
        };
    }
}


class Response
{

	private ResponseType $type;
    public ?string $template = null;
    public mixed $data = null;
    public mixed $payload = null;
    public ?string $contentType = null;
    public ?string $context = null;
    private array $headers = [];
    private ?int $response_code = null;
    private mixed $outputProcessingCallBack = null;

    public function __construct(ResponseType $type)
    {
        $this->type = $type;
    }


	/**
	 * Vratí jako výstup aplikace šablonu
	 *
	 * @param string $filename Název šablony
	 * @param object|null $data Data do šablony
	 * @return object Response
	 */
	public static function Template(string $filename, ?object $data = null) : self
	{
        $resp = new Response(ResponseType::Template);
        $resp->template = $filename;
        $resp->data = $data ?? new \stdClass();
        return $resp;
	}


    /**
     * Vrátí jako výstup aplikace JSON
     * @param object $data;
	 * @return object Response
    */
	public static function JSON($data) : self
	{
        $resp = new Response(ResponseType::Json);
        $resp->contentType = 'application/json';
        $resp->data = $data;
        return $resp;
	}


    public static function FileContent(string $source, ?string $content = null, ?string $filename = null) : self
    {
        $resp = new Response(ResponseType::File);
        $resp->data = $source;
        $resp->contentType = $content;
        $resp->context = $filename;
        return $resp;
    }


    /**
     * Vrátí jako výstup aplikace přesměrování
     * @param string $url;
	 * @return object Response
    */
    public static function Redirect(string $url) : self
    {
        $resp = new Response(ResponseType::Redirect);
        $resp->data = $url;
        return $resp;
    }


    /**
     * Vrátí jako výstup aplikace přesměrování na předchozí stránku
	 * @return object Response
    */
    public static function Previous() : self
    {
        return new Response(ResponseType::Previous);
    }


    /**
     * Vratí jako výstup čistý text
     * @param string $text;
     * @param string $contentType [=text/plain]
     * @param string $charset [=utf-8]
     * @return object Response
     */
    public static function Plain(string $text, ?string $contentType = 'text/plain', ?string $charset = 'utf-8') : self
    {
        $resp = new Response(ResponseType::Plain);
        $resp->contentType = $contentType . '; charset=' . $charset;
        $resp->data = $text;
        return $resp;
    }


    /**
     * Vrátí prázdný výstup
     * @return object Response
     */
    public static function Empty() : self
    {
        return new Response(ResponseType::Empty);
    }


	/**
	 * Nastaví HTTP response kód odpovědi
	 *
	 * @param int $code HTTP kód
	 * @return object Response
	 */
    public function responseCode(HttpStatus | int $code): self
    {
        $this->response_code = $code instanceof HttpStatus ? $code->value : $code;
        return $this;
    }


	public function setOutputProcessing(callable $callBack): self
	{
		$this->outputProcessingCallBack = $callBack;
		return $this;
	}


	public function addSystemInfoHeaders()
	{
		$this->headers[] = 'Debug-Info-Memory: '.round(memory_get_peak_usage() / 1024 / 1024, 3).'MB (peak) / '.round(memory_get_usage() / 1024 / 1024, 3).'MB';
		return $this;
	}


    public function send()
    {
		if ($this->response_code != null)
        {
            $status = HttpStatus::from($this->response_code);
            header("HTTP/1.1 {$status->value} {$status->message()}");
		}

		switch ($this->type) {
            case (ResponseType::Template):
                if ($this->contentType != null)
                {
                    header('Content-type: ' . $this->contentType);
                }

				$view = $this->data;
				if(file_exists(TemplateOutput . $this->template . '.php')) include(TemplateOutput . $this->template . '.php');
				else trigger_error('Runner/Ouput Error: template &quot;' . $this->template . '&quot; not found', E_USER_ERROR);
                break;

			case (ResponseType::Json):
				header('Content-type: application/json');
				echo(json_encode($this->data));
				break;

            case (ResponseType::Redirect):
                header('Location: ' . $this->data);
                break;

            case (ResponseType::Previous):
                if((isset($_SERVER['HTTP_REFERER'])) && ($_SERVER['HTTP_REFERER'] != '')) {
                    header('Location: ' . $_SERVER['HTTP_REFERER']);
                } else {
                    header('Location: ' . Base);
                }
                break;

            case (ResponseType::Plain):
                header('Content-type: ' . $this->contentType);
                echo($this->data);
                break;

            case (ResponseType::File):
                break;

            case (ResponseType::Empty):
                break;

            default:

                break;
        }
    }
}
