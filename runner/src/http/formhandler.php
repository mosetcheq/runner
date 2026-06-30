<?php
namespace Rnr\Http;
use \Config;

enum FormError: int
{
    case Reload = 1;
    case Invalid = 2;
    case Expired = 4;
}


/**
 * Class FormHandler - obsluha formularu
 */
class FormHandler
{

    public int $errors = 0;
    public bool $sended = false;
    public string $sender = '';
    private string $formId = '';
    public ?FormData $formData = null;


    /**
     * Vygeneruje SENDER klíč pro směřovaní formuláře
     * @param string $formId identifikátor formuláře
     * @param bool $HtmlOut false vrátí klíč, true vrátí string s input tagem
     */
    public static function Sender($formId, $HtmlOut = true) : string
    {
        $timeStamp = time();
        $random = bin2hex(random_bytes(8));
        $key = $formId . '|' . $timeStamp . '|' . $random;
        $token = base64_encode($key . '|' . hash_hmac('sha256', $key, Config\Form::SECRET));
        if ($HtmlOut) {
            return ('<input type="hidden" name="_rnrform" value="' . $token . '">');
        } else {
            return $token;
        }

    }



    /**
     * Inicializuje FormHandler
     */
    public function __construct()
    {
        $data = ($_SERVER['REQUEST_METHOD'] == 'GET') ? $_GET : $_POST;

        $this->sender = $_SERVER['HTTP_REFERER'] ?? '';

        if(!isset($data['_rnrform'])) return;

        $decoded = base64_decode($data['_rnrform'], true);
        if($decoded === false) {
            $this->errors = FormError::Invalid->value;
            return;
        }

        $sender = explode('|', $decoded);
        if(count($sender) != 4) {
            $this->errors = FormError::Invalid->value;
            return;
        }

        [$formId, $timeStamp, $random, $hash] = $sender;

        
         $key = $formId . '|' . $timeStamp . '|' . $random;
         $hashed = hash_hmac('sha256', $key, Config\Form::SECRET);
    
        if(!hash_equals($hashed, $hash))
        {
            $this->errors |= FormError::Invalid->value;
            return;
        }
        
        if((Config\Form::TOKEN_TTL > 0) && (time() - (int)$timeStamp > Config\Form::TOKEN_TTL))
        {
             $this->errors |= FormError::Expired->value;
            return;
        }

        // kontrola reload
        if (isset($_COOKIE['_f']) && ($_COOKIE['_f'] === $random))
            {
             $this->errors |= FormError::Reload->value;
        }

        setcookie('_f', $random);

        $this->sended = true;
        $this->formId = $formId;
        $this->formData = new FormData($data);
    }



    /**
     * Zavolá obsluhu formuláře ve třídě AppClass
     * @param class $AppClass třída s obslužným skriptem
     */
    public function callHandler($AppClass): false | Response
    {
        if (($this->sended) && ($this->errors === 0)) {
            $method = 'on' . ucfirst($this->formId) . 'Submit';
            if (method_exists($AppClass, $method)) {
                return $AppClass->$method($this->formData);
            } else {
                throw new FormHandlerException("FormHandler error: Method {$method} for {$this->formId} not found");
            }
        } else {
            return false;
        }
    }


    /**
     * Otestuje zda byl odeslán uvedený formulář a odeslání proběhlo bez chyb
     * @param string $formName název formuláře
     */
    public function isForm($formName) : bool
    {
        return ($this->formId === $formName) && ($this->sended) && ($this->errors === 0);
    }
}



/**
 * Class FormData - data z formularu
 */
class FormData
{

    private array $data;

    /**
     * Konstruktor
     * @param array $data;
     */
    public function __construct(array $data)
    {
        unset($data['_rnrform']);
        $this->data = $data;
    }


    /**
     * getter
     * @param string $key Nazev pole
     * @return string
     */
    public function __get(string $key) : mixed
    {
        return $this->data[$key] ?? null;
    }


    /**
     * Otestování zda pole existuje a případně má uvedenou hodnotu
     * @param string $key název pole
     * @param string $value hodnota pole
     * @return bool
     */
    public function check($key, $value = null) : bool
    {
        if($value == null) {
            return isset($this->data[$key]);
        }
        elseif(isset($this->data[$key])) {
            return($this->data[$key] == $value);
        }
        else return(false);
    }


    /**
     * Vrátí všechny data z formuláře jako pole
     * @return object
     */
    public function getAll(): \stdClass
    {
        return (object) $this->data;
    }


    /**
     * Provede php funkci TRIM na všechny pole formuláře
     * @param string $characters znaky na ořez
     */
    public function trim($characters = false) : self
    {
        foreach ($this->data as $key => $val) {
            $this->data[$key] = (is_array($val) ? array_filter($val) : trim($val, $characters));
        }
        return $this;
    }
}



class FormHandlerException extends \Exception {}