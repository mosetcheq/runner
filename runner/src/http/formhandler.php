<?php
namespace Rnr\Http;
use Rnr\Http\FormData;
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
    private Request $request;
    public int $errors = 0;
    public bool $sended = false;
    public ?string $sender = '';
    private string $formId = '';
    private ?string $controller = '';
    private bool $public = false;
    public ?FormData $formData = null;


    /**
     * Vygeneruje SENDER klíč pro směřovaní formuláře
     * @param string $formId identifikátor formuláře
     * @param bool $htmlOut false vrátí klíč, true vrátí string s input tagem
     * @param bool $public true jestli se má formulář obsloužit ještě před middleware chain
    */
    public static function sender($formId, $public = false, $htmlOut = true) : string
    {
        $timeStamp = time();
        $random = bin2hex(random_bytes(8));
        $key = $formId . '|' . ($public ? 'public' : 'private') . '|' . $timeStamp . '|' . $random;
        $token = base64_encode($key . '|' . hash_hmac('sha256', $key, Config\Form::SECRET));
        if ($htmlOut) {
            return ('<input type="hidden" name="_rnrform" value="' . $token . '">');
        } else {
            return $token;
        }

    }



    /**
     * Inicializuje FormHandler
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
        $data = ($request->getMethod() == 'GET') ? $request->getQueryData() : $request->getPostData();
        $this->sender = $request->getReferer();

        if(!isset($data['_rnrform'])) return;

        $decoded = base64_decode($data['_rnrform'], true);
        if($decoded === false) {
            $this->errors = FormError::Invalid->value;
            return;
        }

        $sender = explode('|', $decoded);
        if(count($sender) != 5) {
            $this->errors = FormError::Invalid->value;
            return;
        }

        [$formId, $public, $timeStamp, $random, $hash] = $sender;

        
         $key = $formId . '|' . $public . '|' . $timeStamp . '|' . $random;
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
        if ($request->getCookie('_f')=== $random)
            {
             $this->errors |= FormError::Reload->value;
        }

        setcookie('_f', $random);

        $this->sended = true;
        $target = explode(':', $formId);
        $this->formId = array_pop($target);
        $this->controller = array_pop($target);
        $this->public = ($public === 'public');
        $this->formData = new FormData($data);
    }



    /**
     * Vrací informaci o tom jestli je formulář veřejný nebo nikoliv
     *
     * @return boolean
     */
    public function isPublic()
    {
        return $this->public;
    }



    /**
     * Zavolá obsluhu formuláře
     */
    public function callHandler(): false | Response
    {
        if (($this->sended) && ($this->errors === 0)) {
            $controllerName = Config\Defaults::CONTROLLER_NAMESPACE . '\\' . ($this->controller ? $this->controller : Config\Defaults::DEFAULT_CONTROLLER);
            $controller = new $controllerName;
            $method = $this->formId;
            if (method_exists($controller, $method)) {
                return $controller->$method($this->formData, $this->request);
            } else {
                throw new FormHandlerException("FormHandler error: Method {$controllerName}::{$method} for {$this->formId} not found");
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


class FormHandlerException extends \Exception {}