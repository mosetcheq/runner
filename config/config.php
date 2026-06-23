<?php
namespace Config;


final class Defaults {
    public const StartClass = 'application';
    public const Method = 'main';

}

final class DB {
    public const Host = '';
    public const Name = '';
    public const User = '';
    public const Password = '';
    public const DebugMode = false;
}

final class Form {
    public const Secret = 'uuiiSecretString';
    public const TokenTTL = 3600;
}

date_default_timezone_set('Europe/Prague');

/* filesystem */
define('AppDir', $_SERVER['DOCUMENT_ROOT'].'/');
define('ConfDir', AppDir.'conf/');
define('AppClassDir', AppDir.'app/');
define('RnrDir', AppClassDir.'rnr/');
define('PSR4', AppDir.'vendor/composer/autoload_psr4.php');

define('TemplateSource', AppDir.'html/');
define('TemplateOutput', AppDir.'template/');
define('UseHTMLCompiler', false);

/* default */
define('defaultModule', 'application');
define('defaultAction', 'main');
define('defaultGlobalAction', 'main');
define('actionPostfix', '');

define('Base', 'http'.(isset($_SERVER['HTTP_HTTPS']) ? 's' : '').'://'.$_SERVER['SERVER_NAME']);
define('BaseStatic', Base);

/* Runner specific */
define('AjaxFlag', 'ajax');
define('formIdentificator', 'formID');
define('rewriteVariable', 'rewrite');
define('DisableWarnings', false);
define('ErrorDocumentName', 'e');
define('AdvLog', true);
define('ErrorEnableSource', true);
define('NoClassAs404', false);
/*
define('ErrorEmail', 'email@domain.tld');
*/

/* PDO MYSQL */
/*
define('DB_host', '');
define('DB_name', '');
define('DB_user', '');
define('DB_password', '');
define('DB_DEBUGMODE', false);
*/

/* URL */
define('ParamModule', 'mod');
define('ParamAction', 'action');

/* others */
define('salt', '');
define('RNRFORM_SECRET', 'uuiiSecretString');
define('RNRFORM_TTL', 3600);