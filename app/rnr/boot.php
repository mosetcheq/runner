<?php namespace {
/* autoloader handler */
function autoloader($className) {
	$classFileName = str_replace('\\', '/', strtolower($className));
	if(file_exists(AppClassDir.$classFileName.'.php')) require_once(AppClassDir.$classFileName.'.php');
	else {
		if(NoClassAs404) {
			if(!class_exists('Rnr\ErrorDocument')) require_once(RnrDir.'errordocument.php');
			class_alias('Rnr\ErrorDocument', $className);
		} else {
			$info = debug_backtrace();
			while(!$info[0]['line']) array_shift($info);
			Rnr\ErrorHandling::Critical(E_ERROR, "Runner Error: Class '{$className}' (".AppClassDir.$className.".php) not found", $info[0]['file'], $info[0]['line'], $info[0]['args']);
		}
	}
}

/* output types - start */

define('OUTPUT_TEMPLATE', 1);
define('OUTPUT_ERRORDOCUMENT', 2);
define('OUTPUT_REDIRECT', 4);
define('OUTPUT_JSON', 8);
define('OUTPUT_PREVIOUS', 32);
define('OUTPUT_PLAIN', 64);
define('OUTPUT_FILE', 128);

class OutputType {
	public $type;
	public $template;
	public $data1;
	public $data2;

	public function __construct($type, $template, $data1 = null, $data2 = null) {
		$this->type = $type;
		$this->template = $template;
		$this->data1 = $data1;
		$this->data2 = $data2;
	}
}


function Template($filename) {
	return new OutputType(OUTPUT_TEMPLATE, $filename, null, null);
}

function Plain($text, $contentType = 'text/plain', $charset = 'utf-8') {
	return new OutputType(OUTPUT_PLAIN, $text, $contentType, $charset);
}

function ErrorDocument($error, $usertemplate = null) {
	return new OutputType(OUTPUT_ERRORDOCUMENT, $usertemplate, $error, null);
}

function Redirect($url, $code = null) {
	return new OutputType(OUTPUT_REDIRECT, null, $url, $code);
}

function JSON($data) {
	return new OutputType(OUTPUT_JSON, null, $data, null);
}

function Previous() {
	return new OutputType(OUTPUT_PREVIOUS, null, null, null);
}

function FileContent($source, $content = null, $filename = null) {
	return new OutputType(OUTPUT_FILE, $source, $content, $filename);
}
/* output types - end */

/* injector */
function Inject($class, $method, $arguments) {
	try {
		$r = new ReflectionMethod($class, $method);
	} catch (Exception $e) {
		trigger_error("Runner ERROR: Unhandled '{$method}' action in '{".class_name($class)."' class", E_USER_ERROR);
	}

	$obj_params = [];
	foreach($r->getParameters() as $p) {
		$className = $p->getClass()->name;
		$obj_params[] = ($className ? new $className : array_shift($arguments));
	}	

	return array_merge($obj_params, $arguments);
}

/* register */
spl_autoload_register('autoloader');


require(RnrDir.'error.php');
require(RnrDir.'db.php');
require(RnrDir.'io.php');
require(RnrDir.'router.php');
require(RnrDir.'tools.php');

use Rnr\utf8;
use Rnr\DB;
use Rnr\StopWatch;
use Rnr\Log;

header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

$GlobalTime = new StopWatch();
if(AdvLog) Log::Write(' *** Starting session *** ');

if(file_exists(RnrDir.'routes.php')) {
	require(RnrDir.'routes.php');
	define('URLModeRouter', true);
	$runnerAction = Rnr\Router($routes, $_GET[rewriteVariable], new Rnr\RouterStatus(defaultModule, defaultAction));
} else {
	$params = $_GET;
	unset($params[ParamModule], $params[ParamAction]);
	$runnerAction = new Rnr\RouterStatus($_GET[ParamModule] ? $_GET[ParamModule] : defaultModule, $_GET[ParamAction], RTR_OK, $params);
}

if(!$runnerAction->method) $runnerAction->method = defaultGlobalAction;

$Runner = new $runnerAction->className;

if(!$output) $output = new OutputType(null, null, null);
if(method_exists($Runner, 'onLoad')) $output = call_user_func_array([$Runner, 'onLoad'], Inject($Runner, 'onLoad', []));

/* Pridavani parametru podle POST dat - Kandidat na DEPRECATED */
if(($_SERVER['REQUEST_METHOD'] == 'POST') && StreamAsParameter){
	switch($_SERVER['CONTENT_TYPE']) {
		case('application/json'):
			array_push($runnerAction->params, JSON_decode(In::GetStream()));
		break;
		case('application/xml'):
			array_push($runnerAction->params, SimpleXML_load_string(In::GetStream()));
		break;
	}
}

/* osetreni POST metody - Kandidát na DEPRECATED */
if(count($_POST) > 0) {
	$postData = $_POST;
	if($_POST[formIdentificator]) {
		$formID = $_POST[formIdentificator];
		$method_name = "on{$formID}Submit";
		unset($postData[formIdentificator]);
		if(is_array($postData[$formID])) $postData = $postData[$formID];
	} else $method_name = 'onPostData';
	if(method_exists($Runner, $method_name)) $output = call_user_func_array([$Runner, $method_name], Inject($Runner, $method_name, $postData));
		elseif(!DisableWarnings) trigger_error("Runner WARNING: Unhandled '{$method_name}/POST' event", E_USER_ERROR);
}

/* osetreni formulare z GET - Kandidát na DEPRECATED */
if($_GET[formIdentificator]) {
	$getData = $_GET;
	$method_name = 'on'.$_GET[formIdentificator].'Submit';
	unset($getData[formIdentificator]);
	$getData = array_filter($getData);
	if(method_exists($Runner, $method_name)) $output = call_user_func_array([$Runner, $method_name], Inject($Runner, $method_name, $getData));
		elseif(!DisableWarnings) trigger_error("Runner WARNING: Unhandled '{$method_name}/GET' event", E_USER_ERROR);
}


if($output->type == null) {
	if(method_exists($Runner, $runnerAction->method)) $output = call_user_func_array([$Runner, $runnerAction->method], Inject($Runner, $runnerAction->method, $runnerAction->params));
	elseif(!DisableWarnings) trigger_error("Runner ERROR: Unhandled '{$runnerAction->method}' action in '{$runnerAction->className}' class", E_USER_ERROR);
}

if(($output->type == OUTPUT_TEMPLATE) && (method_exists($Runner, 'BeforeRender'))) call_user_func_array([$Runner, 'BeforeRender'], Inject($Runner, 'BeforeRender', []));

if(($output->type == null) && (!DisableWarnings)) trigger_error('Runner WARNING: NULL output', E_USER_ERROR);

if($output->type == OUTPUT_ERRORDOCUMENT) {
	if($output->template == null) $output->template = ErrorDocumentName.$output->data1;
	$message = Rnr\Output::$HttpCodes[$output->data1];
	if((!$message) && (!DisabledWarnings)) trigger_error('Runner/Output Error: &quot;'.$output->data1.'&quot; is not valid HTTP code');
	header('HTTP/'.$message);
	$output->type = OUTPUT_TEMPLATE;
}

	header('Memory: '.round(memory_get_peak_usage() / 1024 / 1024, 3).'MB (peak) / '.round(memory_get_usage() / 1024 / 1024, 3).'MB');
	header('Time: '.$GlobalTime);

switch($output->type) {
	case(OUTPUT_TEMPLATE):

		$Runner->view->SCRIPT_TIME = (string)$GlobalTime;

		if(method_exists($Runner, 'Template') && (!$output->data1)) $output = call_user_func_array([$Runner, 'Template'], [$output->template]);
		else {

			$v = $view = $Runner->view->GetAssigned();

			ob_start();

			if(file_exists(TemplateOutput.$output->template.'.php')) include(TemplateOutput.$output->template.'.php');
			else trigger_error('Runner/Ouput Error: template &quot;'.$output->template.'&quot; not found', E_USER_ERROR);

			$output = ob_get_clean();
		}

		$Runner->view->SendHeaders();
		echo($output);

		if(method_exists($Runner, 'AfterRender')) call_user_func_array([$Runner, 'AfterRender'], Inject($Runner, 'AfterRender', []));
		if(method_exists($Runner, 'OutputProcessing')) $output = call_user_func_array([$Runner, 'OutputProcessing'], Inject($Runner, 'OutputProcessing', $output));

	break;

	case(OUTPUT_PLAIN):
		header('Content-type: '.$output->data1.($output->data2 ? ';charset='.$output->data2 : ''));
		echo($output->template);
	break;

	case(OUTPUT_JSON):
       		header('Content-type: application/json');
		echo(json_encode($output->data1));
		exit();
	break;

	case(OUTPUT_REDIRECT):
		if($output->data2) header('Location: '.$output->data1, true, $output->data2);
			else header('Location: '.$output->data1);
	break;

	case(OUTPUT_PREVIOUS):
        	header('Location: '.$_SERVER['HTTP_REFERER']);
	break;

	case(OUTPUT_FILE):
		if($output->data1) header('Content-type: '.$output->data1);
		if($output->data2) header('Content-Disposition: attachment; filename="'.$output->data2.'"');
                readfile($output->template);
	break;
}
flush();
}