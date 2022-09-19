<?php namespace {
/* autoloader handler */
function autoloader($className) {
	if(defined('PSR4'))
	{
		$file = preg_replace($GLOBALS['PSR4_Regex'][0], $GLOBALS['PSR4_Regex'][1], $className);

		if($file == $className)
		{
			$classFileName = AppClassDir . str_replace('\\', '/', strtolower($className));
		}
		else {
			$classFileName = $file;
		}

		$classFileName.= '.php';
	}
	
	if(AdvLog) Rnr\Log::Write('Loading ' . $className . ' (' . $classFileName . ')');
	if(file_exists($classFileName)) require_once($classFileName);
	else {
/*
		require_once(RnrDir.'errordocument.php');
		class_alias('Rnr\ErrorDocument', $className);
*/
		$info = debug_backtrace();
		while(isset($info[0]['line'])) array_shift($info);
		Rnr\ErrorHandling::Critical(E_ERROR, "Runner Error: Class '{$className}' ({$classFileName}) not found", $info[0]['file'] ?? '', $info[0]['line'] ?? '', $info[0]['args'] ?? '');
	}

}

/* output types - start */

function Template($filename) {
	return new Response(OUTPUT_TEMPLATE, $filename, null, null);
}

function Plain($text, $contentType = 'text/plain', $charset = 'utf-8') {
	return new Response(OUTPUT_PLAIN, $text, $contentType, $charset);
}

function ErrorDocument($error, $usertemplate = null) {
	return new Response(OUTPUT_ERRORDOCUMENT, $usertemplate, $error, null);
}

function Redirect($url, $code = null) {
	return new Response(OUTPUT_REDIRECT, null, $url, $code);
}

function JSON($data, $code = null) {
	return new Response(OUTPUT_JSON, null, $data, $code);
}

function Previous() {
	return new Response(OUTPUT_PREVIOUS, null, null, null);
}

function FileContent($source, $content = null, $filename = null) {
	return new Response(OUTPUT_FILE, $source, $content, $filename);
}
/* output types - end */

function HTML($text) {
	return htmlspecialchars($text ?? '');
}


/* register */
spl_autoload_register('autoloader');

require(RnrDir.'base.php');
require(RnrDir.'io.php');
require(RnrDir.'error.php');
require(RnrDir.'db.php');
require(RnrDir.'tools.php');
require(RnrDir.'router.php');

if(defined('PSR4'))
{
	$PSR4 = include(PSR4);
	$PSR4_Regex = [[], []];
	foreach($PSR4 as $namespace => $dir)
	{
		$PSR4_Regex[0][] = '/' . str_replace('\\', '\\\\', $namespace) . '(.*)/i';
		$PSR4_Regex[1][] = $dir[0] . '/$1';
	}
}

use Rnr\utf8;
use Rnr\DB;
use Rnr\StopWatch;
use Rnr\Log;

header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

$GlobalTime = new StopWatch();
if(AdvLog) Log::Write(' *** Starting session *** ');

if(file_exists(AppDir.'conf/routes.php')) {
	require(AppDir.'conf/routes.php');
	define('URLModeRouter', true);
	$url = parse_url($_SERVER['REQUEST_URI']);
	$runnerAction = Rnr\Router(
		$routes,
		urldecode($url['path']),
		new Rnr\RouterStatus(defaultModule, defaultAction)
	);
} else {
	$params = $_GET;
	unset($params[ParamModule], $params[ParamAction]);
	$runnerAction = new Rnr\RouterStatus($_GET[ParamModule] ? $_GET[ParamModule] : defaultModule, $_GET[ParamAction], RTR_OK, $params);
}

if(!$runnerAction->method) $runnerAction->method = defaultGlobalAction;

$Runner = new $runnerAction->className;

if(isset($output)) $output = new Response(null, null, null);
// if(method_exists($Runner, 'onLoad')) $output = call_user_func([$Runner, 'onLoad'], Inject($Runner, 'onLoad', []));
if(method_exists($Runner, 'onLoad')) $output = call_user_func([$Runner, 'onLoad'], []);

/* Pridavani parametru podle POST dat - Kandidat na DEPRECATED */
if($_SERVER['REQUEST_METHOD'] == 'POST'){
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
	if(isset($_POST[formIdentificator])) {
		$formID = $_POST[formIdentificator];
		$method_name = "on{$formID}Submit";
		unset($postData[formIdentificator]);
		if(isset($postData[$formID]) && is_array($postData[$formID])) $postData = $postData[$formID];
	} else $method_name = 'onPostData';

	// if(method_exists($Runner, $method_name)) $output = call_user_func_array([$Runner, $method_name], Inject($Runner, $method_name, [$postData]));
	if(method_exists($Runner, $method_name)) $output = call_user_func_array([$Runner, $method_name], [$postData]);
//		elseif(!DisableWarnings) trigger_error("Runner WARNING: Unhandled '{$method_name}/POST' event", E_USER_ERROR);
}

/* osetreni formulare z GET - Kandidát na DEPRECATED */
if(isset($_GET[formIdentificator])) {
	$getData = $_GET;
	$method_name = 'on'.$_GET[formIdentificator].'Submit';
	unset($getData[formIdentificator]);
	$getData = array_filter($getData);
	// if(method_exists($Runner, $method_name)) $output = call_user_func_array([$Runner, $method_name], Inject($Runner, $method_name, $getData));
	if(method_exists($Runner, $method_name)) $output = call_user_func_array([$Runner, $method_name], $getData);
		elseif(!DisableWarnings) trigger_error("Runner WARNING: Unhandled '{$method_name}/GET' event", E_USER_ERROR);
}


if(empty($output->type)) {
	// if(method_exists($Runner, $runnerAction->method)) $output = call_user_func_array([$Runner, $runnerAction->method], Inject($Runner, $runnerAction->method, $runnerAction->params));
	if(method_exists($Runner, $runnerAction->method)) $output = call_user_func_array([$Runner, $runnerAction->method], $runnerAction->params);
	elseif(method_exists($Runner, '__missing')) $output = call_user_func_array([$Runner, '__missing'], [$runnerAction->method]);
	elseif(!DisableWarnings) trigger_error("Runner ERROR: Unhandled '{$runnerAction->method}' action in '{$runnerAction->className}' class", E_USER_ERROR);
}

// if(($output->type == OUTPUT_TEMPLATE) && (method_exists($Runner, 'BeforeRender'))) call_user_func_array([$Runner, 'BeforeRender'], Inject($Runner, 'BeforeRender', []));
if(($output->type == OUTPUT_TEMPLATE) && (method_exists($Runner, 'BeforeRender'))) call_user_func_array([$Runner, 'BeforeRender'], []);

if(($output->type == null) && (!DisableWarnings)) trigger_error('Runner WARNING: NULL output', E_USER_ERROR);

if($output->type == OUTPUT_ERRORDOCUMENT) {
	if($output->template == null) $output->template = ErrorDocumentName.$output->data1;
	$message = Rnr\Output::$HttpCodes[$output->data1];
	if(method_exists($Runner, 'BeforeRender')) call_user_func_array([$Runner, 'BeforeRender'], []);
	if((!$message) && (!DisabledWarnings)) trigger_error('Runner/Output Error: &quot;'.$output->data1.'&quot; is not valid HTTP code');
	header('HTTP/'.$message);
	$output->type = OUTPUT_TEMPLATE;
}

	header('Memory: '.round(memory_get_peak_usage() / 1024 / 1024, 3).'MB (peak) / '.round(memory_get_usage() / 1024 / 1024, 3).'MB');
	header('Time: '.$GlobalTime);

switch($output->type) {
	case(OUTPUT_TEMPLATE):

		if(UseHTMLCompiler) {
			include(RnrDir.'templatecompiler.php');
			new Template($output->template);
		}
		$Runner->view->SendHeaders();
		$v = $view = $Runner->view->GetAssigned();
		$v->SCRIPT_TIME = (string)$GlobalTime;

		ob_start();

		if(file_exists(TemplateOutput.$output->template.'.php')) include(TemplateOutput.$output->template.'.php');
	        elseif(file_exists(TemplateOutputCommon.$output->template.'.php')) include(TemplateOutputCommon.$output->template.'.php');
		else trigger_error('Runner/Ouput Error: template &quot;'.$output->template.'&quot; not found', E_USER_ERROR);

		$output = ob_get_clean();

		//if(method_exists($Runner, 'AfterRender')) call_user_func_array([$Runner, 'AfterRender'], Inject($Runner, 'AfterRender', []));
		if(method_exists($Runner, 'AfterRender')) call_user_func_array([$Runner, 'AfterRender'], []);
		//if(method_exists($Runner, 'OutputProcessing')) $output = call_user_func_array([$Runner, 'OutputProcessing'], Inject($Runner, 'OutputProcessing', $output));
		if(method_exists($Runner, 'OutputProcessing')) $output = call_user_func_array([$Runner, 'OutputProcessing'], [$output]);

		echo($output);

	break;

	case(OUTPUT_PLAIN):
		header('Content-type: '.$output->data1.($output->data2 ? ';charset='.$output->data2 : ''));
		echo($output->template);
	break;

	case(OUTPUT_JSON):
		if($output->data2) header('HTTP/'.Rnr\Output::$HttpCodes[$output->data2]);
       	header('Content-type: application/json');
		echo(json_encode($output->data1));
		exit();
	break;

	case(OUTPUT_REDIRECT):
		if($output->data2) header('Location: '.$output->data1, true, $output->data2);
			else header('Location: '.$output->data1);
	break;

	case(OUTPUT_PREVIOUS):
		if(isset($_SERVER['HTTP_REFERER'])) {
        	header('Location: ' . $_SERVER['HTTP_REFERER']);
		} else {
			header('Location: ' . Base);
		}
	break;

	case(OUTPUT_FILE):
		if($output->data1) header('Content-type: '.$output->data1);
		if($output->data2) header('Content-Disposition: attachment; filename="'.$output->data2.'"');
                readfile($output->template);
	break;
}
flush();
}