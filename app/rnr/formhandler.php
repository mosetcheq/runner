<?php
namespace Rnr;

define('RNRFORM_ERR_RELOAD', 1);
define('RNRFORM_ERR_IP', 2);
define('RNRFORM_ERR_SERVER', 4);

class FormHandler {


        public $errors = 0;
        public $sended = false;
        public $sender = '';
        private $FormID = '';
        public $FormData;


        public static function Sender($FormId, $HtmlOut = true) {
                $key = base64_encode($FormId.'|'.microtime(true).'|'.$_SERVER['REMOTE_ADDR'].'|'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
                if($HtmlOut) return('<input type="hidden" name="_rnrform" value="'.$key.'">');
                else return $key;
        }


        public function __construct() {
                if($_SERVER['REQUEST_METHOD'] == 'GET') $data = $_GET; else $data = $_POST;
                $this->sender = $_SERVER['HTTP_REFERER'];
                if(isset($data['_rnrform'])) {
                        $sender = explode('|', base64_decode($data['_rnrform']));
                        $this->sended = true;
                        $this->FormID = $sender[0];
                        if($sender[1] == $_COOKIE['_f']) $this->errors = $this->errors | RNRFORM_ERR_RELOAD;
                        if($sender[2] != $_SERVER['REMOTE_ADDR']) $this->errors = $this->errors | RNRFORM_ERR_IP;
                        if($sender[3] != preg_replace('/http(s*):\/\//', '', $this->sender)) $this->errors = $this->errors | RNRFORM_ERR_SERVER;
                        setcookie('_f', $sender[1]);
                        $this->FormData = new FormData($data);
                }
        }


        public function CallHandler($AppClass) {
                if(($this->sended) && ($this->errors === 0)) {
                        $method = 'on'.ucfirst($this->FormID).'Submit';
                        if(method_exists($AppClass, $method)) return call_user_func_array([$AppClass, $method], [$this->FormData]);
                        else {
                                ErrorHandling::Critical(E_ERROR, "Runner Error: Form handler '{$method}' not found");
                                return false;
                        }
                } else return false;
        }
}


class FormData {

        private $data;


        public function __construct($data) {
                unset($data['_rnrform']);
                $this->data = $data;
        }


        public function __get($key) {
                return $this->data[$key];
        }


        public function GetAll() {
                return (object)$this->data;
        }


        public function Trim($characters) {
                foreach($this->data as $key => $val) $this->data[$key] = (is_array($val) ? array_filter($val) : trim($val, $characters));
        }
}