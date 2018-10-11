<?php

date_default_timezone_set('Europe/Prague');

/* filesystem */
define('AppDir', $_SERVER['DOCUMENT_ROOT'].'/');
define('ConfDir', AppDir.'conf/');
define('AppClassDir', AppDir.'app/');
define('RnrDir', AppClassDir.'rnr/');

define('TemplateSource', AppDir.'html/');
define('TemplateOutput', AppDir.'template/');

/* default */
define('defaultModule', 'application');
define('defaultAction', 'main');
define('defaultGlobalAction', 'main');
define('actionPostfix', '');

define('Base', 'http'.($_SERVER['HTTP_HTTPS'] ? 's' : '').'://'.$_SERVER['SERVER_NAME']);
define('BaseStatic', Base);

/* Runner specific */
define('AjaxFlag', 'ajax');
define('formIdentificator', 'formID');
define('rewriteVariable', 'rewrite');
define('DisableWarnings', false);
define('ErrorDocumentName', 'e');
define('AdvLog', false);
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
