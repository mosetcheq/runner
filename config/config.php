<?php
namespace Config;


final class Path {
    public const DOC_ROOT = 'd:/web/_runner/';
    public const RNR = self::DOC_ROOT . 'runner/src/';
    public const APP = self::DOC_ROOT . 'app/';
    public const TEMPLATES = self::DOC_ROOT . 'app/templates/';
    public const ETC = self::DOC_ROOT . 'etc/';
    public const CACHE = self::DOC_ROOT . 'cache/';

}

final class Defaults {
    public const DEFAULT_CONTROLLER = \App\Controllers\Home::class;
    public const DEFAULT_METHOD = 'index';
    public const CONTROLLER_NAMESPACE = '\\App\\Controllers';
    public const MIDDLEWARE_NAMESPACE = '\\App\\Middleware';
    public const CONTROLLER_POSTFIX = 'Controller';
    public const METHOD_POSTFIX = '';
}

final class DB {
    public const HOST = '';
    public const NAME = '';
    public const USER = '';
    public const PASSWORD = '';
    public const DEBUG_MODE = false;
}

final class Form {
    public const SECRET = 'uuiiSecretString';
    public const TOKEN_TTL = 3600;
}

date_default_timezone_set('Europe/Prague');

/* filesystem */
/*
define('AppDir', $_SERVER['DOCUMENT_ROOT'].'/');
define('ConfDir', AppDir.'conf/');
define('AppClassDir', AppDir.'app/');
define('RnrDir', AppClassDir.'rnr/');
define('PSR4', AppDir.'vendor/composer/autoload_psr4.php');

define('TemplateSource', AppDir.'html/');
define('TemplateOutput', AppDir.'template/');
define('UseHTMLCompiler', false);
*/
/* default */
/*
define('defaultModule', 'application');
define('defaultAction', 'main');
define('defaultGlobalAction', 'main');
define('actionPostfix', '');

define('Base', 'http'.(isset($_SERVER['HTTP_HTTPS']) ? 's' : '').'://'.$_SERVER['SERVER_NAME']);
define('BaseStatic', Base);
*/
/* Runner specific */
/*
define('AjaxFlag', 'ajax');
define('formIdentificator', 'formID');
define('rewriteVariable', 'rewrite');
define('DisableWarnings', false);
define('ErrorDocumentName', 'e');
define('AdvLog', true);
define('ErrorEnableSource', true);
define('NoClassAs404', false);
*/
/*
define('ErrorEmail', 'email@domain.tld');
*/
