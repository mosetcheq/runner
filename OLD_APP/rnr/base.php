<?php

define('RESPONSE_TEMPLATE', 1);
define('RESPONSE_ERRORDOCUMENT', 2);
define('RESPONSE_REDIRECT', 4);
define('RESPONSE_JSON', 8);
define('RESPONSE_PREVIOUS', 32);
define('RESPONSE_PLAIN', 64);
define('RESPONSE_FILE', 128);

/* output types - start */

/**
 * Vratí jako výstup aplikace šablonu
 *
 * @param string $filename Název šablony
 * @param object $data Data do šablony
 * @return object Response
 */
function Template($filename, $data)
{
    return new Response(RESPONSE_TEMPLATE, $filename, $data);
}

function Plain($text, $contentType = 'text/plain', $charset = 'utf-8')
{
    $res              = new Response(RESPONSE_PLAIN, $text, $contentType, $charset);
    $res->contentType = $contentType . '; charset=' . $charset;
    return $res;
}

function ErrorDocument($error, $usertemplate = null)
{
    return new Response(RESPONSE_ERRORDOCUMENT, $usertemplate, $error, null);
}

function Redirect($url, $code = null)
{
    return new Response(RESPONSE_REDIRECT, null, $url, $code);
}

function JSON($data)
{
	$res = new Response(RESPONSE_JSON, null, $data);
	$res->contentType = 'application/json';
    return $res;

}

function Previous()
{
    return new Response(RESPONSE_PREVIOUS, null, null, null);
}

function FileContent($source, $content = null, $filename = null)
{
    return new Response(RESPONSE_FILE, $source, $content, $filename);
}
/* output types - end */


class Response
{

	public $type;
    public $template;
    public $data;
    public $data1;
    public $data2;

    private $headers       = [];
    private $response_code = null;
	private $outputProcessingCallBack = null;
    public $contentType   = null;

    public function __construct($type = null, $template = null, $data1 = null, $data2 = null)
    {
        $this->type     = $type;
        $this->template = $template;
        $this->data1    = $data1;
        $this->data2    = $data2;
    }


	/**
	 * Vratí jako výstup aplikace šablonu
	 *
	 * @param string $filename Název šablony
	 * @param object $data Data do šablony
	 * @return object Response
	 */
	public static function Template($filename, $data)
	{
        $resp = new Response(RESPONSE_TEMPLATE);
        $resp->template = $filename;
        $resp->data = $data;
        return $resp;
	}


    /**
     * Vrátí jako výstup aplikace JSON
     * @param object $data;
	 * @return object Response
    */
	public static function JSON($data)
	{
        $resp = new Response(RESPONSE_JSON);
        $resp->contentType = 'application/json';
        $resp->data = $data;
        return $resp;
	}


    public static function FileContent($source, $content = null, $filename = null)
    {
        return new Response(RESPONSE_FILE);
    }


    /**
     * Vrátí jako výstup aplikace přesměrování
     * @param string $url;
	 * @return object Response
    */
    public static function Redirect($url)
    {
        $resp = new Response(RESPONSE_REDIRECT);
        $resp->url = $url;
        return $resp;
    }


    /**
     * Vrátí jako výstup aplikace přesměrování na předchozí stránku
	 * @return object Response
    */
    public static function Previous() {
        return new Response(RESPONSE_PREVIOUS);

    }


	private static $HttpCodes = [
        100 => '1.0 100 Continue',
        101 => '1.0 101 Switching protocol',
        200 => '1.0 200 OK',
        201 => '1.0 201 Created',
        202 => '1.0 202 Accepted',
        203 => '1.1 203 Non-authoritative information',
        204 => '1.0 204 No content',
        205 => '1.0 205 Reset Content',
        206 => '1.0 206 Partial Content',
        300 => '1.0 300 Multiple Choices',
        301 => '1.0 301 Moved Permanently',
        302 => '1.0 302 Found',
        303 => '1.1 303 See Other',
        304 => '1.0 304 Not Modified',
        305 => '1.1 305 Use Proxy',
        307 => '1.1 307 Temporary Redicect',
        400 => '1.0 400 Bad Request',
        401 => '1.0 401 Unauthorised',
        403 => '1.0 403 Forbidden',
        404 => '1.0 404 Not Found',
        405 => '1.0 405 Method Not Allowed',
        406 => '1.0 406 Not Acceptable',
        407 => '1.0 407 Proxy Authentication Required',
        408 => '1.0 408 Request Timeout',
        409 => '1.0 409 Conflict',
        410 => '1.0 410 Gone',
        411 => '1.0 411 Length Required',
        412 => '1.0 412 Precondition Failed',
        413 => '1.0 413 Request Entity Too Large',
        414 => '1.0 414 Request-URI Too Long',
        415 => '1.0 415 Unsuported Media Type',
        416 => '1.0 416 Requested Range Not Satisfiable',
        417 => '1.0 417 Expectation Failed',
    ];




	/**
	 * Nastaví HTTP response kód odpovědi
	 *
	 * @param int $code HTTP kód
	 * @return object Response
	 */
    public function responseCode($code)
    {
        $this->response_code = $code;
        return $this;
    }


	public function setOutputProcessing($callBack)
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
		if ($this->response_code != null) {
			header('HTTP/' . Response::$HttpCodes[$this->response_code]);
		}

		switch ($this->type) {
            case (RESPONSE_TEMPLATE):
                if ($this->contentType != null) {
                    header('Content-type: ' . $this->contentType);
                }

				$view = $this->data;
				if(file_exists(TemplateOutput . $this->template . '.php')) include(TemplateOutput . $this->template . '.php');
				elseif(file_exists(TemplateOutputCommon . $this->template.'.php')) include(TemplateOutputCommon . $this->template .'.php');
				else trigger_error('Runner/Ouput Error: template &quot;' . $this->template . '&quot; not found', E_USER_ERROR);
                break;

			case (RESPONSE_JSON):
				header('Content-type: application/json');
				echo(json_encode($this->data));
				exit();
				break;

            case (RESPONSE_REDIRECT):
                header('Location: '.$this->url);
                break;

            case (RESPONSE_PREVIOUS):
                if((isset($_SERVER['HTTP_REFERER'])) && ($_SERVER['HTTP_REFERER'] != '')) {
                    header('Location: ' . $SERVER['HTTP_REFERER']);
                } else {
                    header('Location: ' . Base);
                }
            default:

                break;
        }
    }
}
