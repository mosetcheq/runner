<?php
namespace Rnr;
use Rnr\Input;

class ErrorHandling {

	private static $runtimeErrors = array();
	private static $E_TYPE = array(
		1 => 'E_ERROR',
		2 => 'E_WARNING',
		4 => 'E_PARSE',
		8 => 'E_NOTICE',
		16 => 'E_CORE_ERROR',
		32 => 'E_CORE_WARNING',
		64 => 'E_COMPILE_ERROR',
		128 => 'E_COMPILE_WARNING',
		256 => 'E_USER_ERROR',
		512 => 'E_USER_WARNING',
		1024 => 'E_USER_NOTICE',
		2048 => 'E_STRICT',
		4096 => 'E_RECOVERABLE_ERROR',
		8192 => 'E_DEPRECATED',
		16384 => 'E_USER_DEPRECATED'
	);

	public static function ShutDown() {
		$halt_on = E_ERROR | E_PARSE | E_USER_ERROR | E_WARNING;
		$error = error_get_last();
		if((count(self::$runtimeErrors) > 0) || (($error['type'] ?? 0) & $halt_on))  {
			if($error['type'] & $halt_on) $error_r = array_values($error);
				else $error = array_pop(self::$runtimeErrors);
			if($error['type'] & $halt_on) self::Critical($error['type'], $error['message'], $error['file'], $error['line']);
				else self::RuntimeReport();
		}
	}

	public static function Runtime($err_no, $err_str, $err_file, $err_line, $err_context = null) {
		self::$runtimeErrors[] = [
			'type' => $err_no,
			'message' => $err_str,
			'file' => $err_file,
			'line' => $err_line
		];
		if($err_no != E_USER_ERROR) return true;
			else return false;
	}

	public static function Critical($err_no, $err_str = null, $err_file = null, $err_line = null, $err_context = null) {
		ob_clean();
		ob_start();

		if(($err_no != E_USER_ERROR) && ($err_file) && ErrorEnableSource) {
			$start_add = $end_add = true;
			$source = file($err_file);
			$eline = $err_line - 1;
			$start = $err_line - 10;
			if($start<0) {
				$start = 0;
				$start_add = false;
				}
			$end = $err_line + 10;
			if($end>=sizeof($source)) {
				$end = sizeof($source)-1;
				$end_add = false;
				}

			$patterns = array(
				'/(abstract|and|array|break|callable|case|catch|class|clone|const|continue|declare|default|die|do|echo|else|elseif|empty|enddeclare|endfor|endforeach|endif|endswitch|endwhile|eval|exit|extends|final|finally|for|foreach|function|global|goto|if|implements|include|include_once|instanceof|insteadof|interface|isset|list|namespace|new|or|print|private|protected|public|require|require_once|return|static|switch|throw|trait|try|unset|use|var|while|xor|yeld)(\W)/i',
                                '/(null|bool|boolean|int|integer|float|decimal|str|string|object|resource|true|false)(\W)/i',
				'/(\$(\w|\d|_)*)/',
				'/(\'[^\']*\')/',
				'/(&quot;[^\']*&quot;)/'
			);
			$replaces = array(
				'<span class="keyw">$1</span>$2',
                '<span class="type">$1</span>$2',
				'<span class="var">$1</span>',
				'<span class="str">$1</span>',
				'<span class="str">$1</span>'
			);
			for($line = $start; $line <= $end; $line++) {
				$source[$line] = htmlspecialchars(rtrim($source[$line]));
				$source[$line] = preg_replace($patterns, $replaces, $source[$line]);
				}
		} else $err_file = null;

		header('HTTP/1.0 500 Internal server error');
		if(Input::IsAjax()) {
			header('Content-type: application/json');
			echo(JSON_encode([
				'TYPE' => 'Critical',
				'SERVER' => $_SERVER,
				'ERROR' => $err_str,
				'FILE' => $err_file,
				'LINE' => $err_line,
				'CONTEXT' => $err_context,
				'BACKTRACE' => debug_backtrace()
			]));
			exit;
		}
		include(RnrDir.'error_critical.php');
		self::send('Critical error');
		exit;
	}

	public static function SQL($query, $info) {
		ob_clean();
		ob_start();

        	$trace = debug_backtrace();
		if(is_array($info)) {
			$error = $info[2];
			array_shift($trace);
		} else $error = $info;
		$err_file = $trace[1]['file'];
		$err_line = $trace[1]['line'];

                if($err_file && ErrorEnableSource) {
			$start_add = $end_add = true;
			$source = file($err_file);
			$eline = $err_line - 1;
			$start = $err_line - 10;
			if($start<0) {
				$start = 0;
				$start_add = false;
				}
			$end = $err_line + 10;
			if($end>=sizeof($source)) {
				$end = sizeof($source)-1;
				$end_add = false;
				}

			$patterns = array(
				'/(abstract|and|array|break|callable|case|catch|class|clone|const|continue|declare|default|die|do|echo|else|elseif|empty|enddeclare|endfor|endforeach|endif|endswitch|endwhile|eval|exit|extends|final|finally|for|foreach|function|global|goto|if|implements|include|include_once|instanceof|insteadof|interface|isset|list|namespace|new|or|print|private|protected|public|require|require_once|return|static|switch|throw|trait|try|unset|use|var|while|xor|yeld)(\W)/i',
                                '/(null|bool|boolean|int|integer|float|decimal|str|string|object|resource|true|false)(\W)/i',
				'/(\$(\w|\d|_)*)/',
				'/(\'[^\']*\')/',
				'/(&quot;[^\']*&quot;)/'
			);
			$replaces = array(
				'<span class="keyw">$1</span>$2',
                                '<span class="type">$1</span>$2',
				'<span class="var">$1</span>',
				'<span class="str">$1</span>',
				'<span class="str">$1</span>'
			);
			for($line = $start; $line <= $end; $line++) {
				$source[$line] = htmlspecialchars(rtrim($source[$line]));
				$source[$line] = preg_replace($patterns, $replaces, $source[$line]);
				}
                }

		header('HTTP/1.0 500 Internal server error');
		if(Input::IsAjax()) {
			header('Content-type: application/json');
			echo(JSON_encode([
				'TYPE' => 'SQL',
				'SERVER' => $_SERVER,
				'ERROR' => $info[2],
				'QUERY' => $query,
				'PDO_info' => $info
			]));
			exit;
		}
        	include(RnrDir.'error_sql.php');
                self::send('SQL error');
		exit;
	}

	public static function RuntimeReport() {
		ob_start();
		$errors = self::$runtimeErrors;
		header('HTTP/1.0 500 Internal server error');
		if(Input::IsAjax()) {
			header('Content-type: application/json');
			echo(JSON_encode([
				'TYPE' => 'Runtime',
				'SERVER' => $_SERVER,
				'ERROR' => $info[2],
				'QUERY' => '',
				'PDO_info' => $info
			]));
			exit;
			}
		include(RnrDir.'error_runtime.php');
		self::send('Rutime errors');
		exit;
	}

	public static function IsErrors() {
		return(count(self::$runtimeErros) > 0);
	}


	public static function send($err_type) {
		$data = ob_get_clean();
		if(defined('ErrorEmail')) mail(ErrorEmail, $_SERVER['SERVER_NAME'].' '.$err_type, $data, "Content-transfer-encoding: 8bit\nContent-type: text/html; charset=utf-8\n");
		if(!DisableWarnings) echo($data);
	}
}


set_error_handler('Rnr\ErrorHandling::Runtime', E_ALL ^ E_NOTICE);
register_shutdown_function('Rnr\ErrorHandling::Shutdown');
