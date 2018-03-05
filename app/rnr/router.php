<?php
namespace Rnr;

class RouterStatus {
	public $className = null;
	public $method = null;
	public $status = null;
	public $params = null;

	public function __construct($class, $method, $status = null, $params = array()) {
		$this->className = $class;
		$this->method = $method;
		$this->status = $status;
		$this->params = $params;
	}
}

define('RTR_TEXT', 0);
define('RTR_REQUIRED', 1);
define('RTR_REGEXP', 2);
define('RTR_OPTIONAL', 3);
define('RTR_ALL', 4);
define('RTR_SKIP', 5);

define('RTR_CONTINUE', 1);
define('RTR_NOCHECKSIZE', 2);
define('RTR_REVERSE', 4);
define('RTR_ADDPARAMS', 8);
define('RTR_ADDPARAMSARRAY', 16);
define('RTR_API_MODE', 32);

define('RTR_NONE', 0);
define('RTR_OK', 1);
define('RTR_NOTFOUND', -1);

function Router($routes, $request, $defaults = null) {
	if(is_array($routes)) {
		$exit = false;
		$request = array_filter(explode('/', $request), function($i) {return trim($i) != '';});
		if(count($request) > 0)	{
			while(($route = array_shift($routes)) && (!$exit)) {
				$passed = true; $worker = $request;
				$params = array();

				if($route['flags'] & RTR_REVERSE) $worker = array_reverse($worker);
				if(!($route['flags'] & RTR_NOCHECKSIZE) && (count($worker) != count($route['route']))) $passed = false;

				while(($element = array_shift($route['route'])) && ($passed)) {
					$u_item = array_shift($worker);
					switch($element[1]) {
						case RTR_TEXT:
							if($element[0] == $u_item) $passed = true;
							else $passed = false;
						break;
						case RTR_REQUIRED:
							if($u_item) $params[] = $u_item;
							else $passed = false;
						break;
						case RTR_REGEXP:
	                                        	if(preg_match("/{$element[0]}/", $u_item, $tmp)>0) $params[] = $tmp[1];
							else $passed = false;
						break;
						case RTR_OPTIONAL:
							if($u_item) $params[] = $u_item;
						break;
						case RTR_ALL:
                                                        array_unshift($worker, $u_item);
							$params[] = implode('/', $worker);
						break;
						case RTR_SKIP:
                                                	array_unshift($worker, $u_item);
						break;
						default:
							$passed = false;
						break;
					}
				}
				if($passed) {
					list($class, $method) = $route['action'];
					if(strpos($class, '*') !== false) $class = rtrim(str_replace('*', array_shift($params), $class),'_\\');
					if($method == '*') $method = array_shift($params);
					if($route['flags'] & RTR_API_MODE) $method = 'on'.ucfirst(strtolower($_SERVER['REQUEST_METHOD'])).ucfirst($method);
					if(($route['flags'] & RTR_ADDPARAMS) && (count($worker) > 0)) $params = array_merge($params, $worker);
                                        if(($route['flags'] & RTR_ADDPARAMSARRAY) && (count($worker) > 0)) $params = array($worker);
                                        $output = new RouterStatus($class, $method.actionPostfix, RTR_OK, $params);
					if(!($route['flags'] & RTR_CONTINUE)) $exit = true;
				}
			}
			if(!$output) $output = new RouterStatus($defaults->className, $defaults->method.actionPostfix, RTR_NOTFOUND, $request);
		return $output;
		} else return new RouterStatus($defaults->className, $defaults->method.actionPostfix, RTR_NONE);
	} else return false;
}
