<?php


define('NODE_ROOT', -1);
define('NODE_TEXT', 0);
define('NODE_SINGLE', 1);
define('NODE_PAIR', 2);


define('PHP_START', '<?php');
define('PHP_STOP', '?'.'>');

define('PHP_ECHO_START', '<?=');
define('PHP_ECHO_STOP', ';?'.'>');

class Node {
	public $nodeName;
	public $nodeValue;
	public $nodeType;
	public $rawData;
	public $parentNode = null;
	public $attributes = array();
	private $childNodes = array();

	private $phpVarObject = true;
	private $phpVarPrefix = 'view->';

	public function __construct($nodeName = null, $nodeValue = null, $nodeType = null, $parentNode = null, $attributes = null, $rawdata = null) {
		$this->nodeName = $nodeName;
		$this->nodeValue = $nodeValue;
		$this->nodeType = $nodeType;
		$this->parentNode = $parentNode;
		$this->attributes = $attributes;
		$this->rawData = $rawdata;
		return $this;
		}

    /*
     * Nastavi hodnotu atributu alementu
     *
     * @param string $atrib nazev atributu
     * @param string $value hodnota atributu
     * @return object Node
     */
	public function setAttribute($atrib, $value) {
		$this->attributes[$atrib] = $value;
		return $this;
		}

    /*
     * Vrati hodnotu atributu elementu
     *
     * @param string $atrib nazev atributu
     * @return string
     */
	public function getAttribute($atrib) {
		return $this->attributes[$atrib];
		}

    /*
     * Vrati pole atributu elementu
     *
     * @return array
     */
	private function getAttributes() {
		$tmp = null;
		if(count($this->attributes)>0) foreach($this->attributes as $attr => $value) if($value) $tmp.= ' '.$attr.'="'.$this->replaceVariables($value).'"';
		return $tmp;
		}

	public function appendChild($node) {
		$this->childNodes[] = $node;
		}

	private function replaceVariables($input) {
		return preg_replace_callback('/\{\{(\$|\#)(\S*)\}\}/', 'Node::replaceVariablesCallBack', $input);
		}
	private function replaceVariablesCallBack($var) {
		if($var[1] == '$') $var = PHP_ECHO_START.self::getVariable($var[2]).PHP_ECHO_STOP;
		if($var[1] == '#') $var = PHP_ECHO_START.$var[2].PHP_ECHO_STOP;
		return $var;
	}

	private function replaceConstants($input) {
		return preg_replace('/\{\{#(\S*)\}\}/', PHP_ECHO_START.'$1'.PHP_ECHO_STOP, $input);
		}




	private function replaceInsideVariables($input) {
		return preg_replace_callback('/\{\{\$(\S*)\}\}/', 'Node::replaceInsideVariablesCallBack', $input);
	}


	private function replaceInsideVariablesCallBack($var) {
			$var = self::getVariable($var[1]);
		return $var;
	}

	private function getVariable($var) {
		if($this->phpVarObject) $var = '$'.$this->phpVarPrefix.str_replace('.', '->', $var);
			else {
				$velm = explode('.', $var);
				$var = '$'.$this->phpVarPrefix."['".implode("']['", $velm)."']'";
				}
		return $var;
		}

	private function getChildNodes($env = null) {
		$sub = null;
		if(count($this->childNodes)>0) foreach($this->childNodes as $node) $sub.= $node->toString($env);
		return $sub;
		}

	private function getChildContents($env = null) {
		$sub = null;
		if(count($this->childNodes)>0) foreach($this->childNodes as $node) $sub.= $node->rawData.$node->toStringNoParse($env);
		// if(count($this->childNodes)>0) foreach($this->childNodes as $node) $sub.= $node->nodeValue.$node->rawData;
		return $sub;
		}

	public function toStringNoParse($env = null) {
		return $this->nodeName.$this->getChildContents($env).$this->nodeValue.$node->rawData;
		}

	public function toString($env = null) {
		$out = '';
		if($this->nodeType == NODE_ROOT) $out = $this->getChildNodes();
		elseif($this->nodeType == NODE_TEXT) $out = $this->replaceVariables($this->nodeValue);
		else {
			if(method_exists($this, $this->nodeName.'Transformer')) call_user_func(array($this,$this->nodeName.'Transformer'), $env);
			$pre_tag = $this->pre_tag;
			$post_tag = $this->post_tag;
			$notag = $this->attributes['notag'];

            // vyjimka pro include, nepouziva se Transforer
			if($this->nodeName == 'include') {
				$notag = 1;
				if($this->attributes['variable']) $pre_tag = PHP_START.' include('.$this->getVariable($this->attributes['variable']).'.\'.php\'); '.PHP_STOP;
					elseif($this->attributes['src']) $pre_tag = PHP_START.' include(\''.$this->attributes['src'].'.php\'); '.PHP_STOP;
				}

			if($this->nodeName == 'alternate') {
				$notag = 1;
				$pre_tag = ' else { '.PHP_STOP;
				$post_tag = PHP_START.' } '.PHP_STOP;
				}

			if($this->attributes['if-exists']) {
				$pre_tag = PHP_START.' if('.$this->getVariable($this->attributes['if-exists']).') { '.PHP_STOP;
				$post_tag = PHP_START.' } '.(!$this->attributes['alternate'] ? PHP_STOP : '');
				$this->attributes['if-exists'] = $this->attributes['alternate'] = null;
				}

			if($this->attributes['if']) {
				$pre_tag = PHP_START.' if('.$this->getVariable($this->attributes['if']);
					if($this->attributes['equal']) $pre_tag.= '=='.$this->attributes['equal'];
                                        if($this->attributes['not-equal']) $pre_tag.= '!='.$this->attributes['not-equal'];
                                        if($this->attributes['less']) $pre_tag.= '<'.$this->attributes['less'];
                                        if($this->attributes['greater']) $pre_tag.= '>'.$this->attributes['greater'];
				$pre_tag.=') { '.PHP_STOP;
				$post_tag = PHP_START.' } '.(!$this->attributes['alternate'] ? PHP_STOP : '');
				$this->attributes['if'] = $this->attributes['alternate'] = $this->attributes['equal'] = $this->attributes['not-equal'] = $this->attributes['less'] = $this->attributes['greater'] = null;
				}

			if($this->attributes['init-binding']) {
				$pre_tag = PHP_START.' if($this->'.$this->attributes['init-binding'].'_init()) { '.PHP_STOP;
				$post_tag = PHP_START.' } '.(!$this->attributes['alternate'] ? PHP_STOP : '');
				$this->attributes['init-binding'] = $this->attributes['alternate'] = null;
				}

			if($this->attributes['binding']) {
				if(!$this->attributes['bind-as']) $this->attributes['bind-as'] = 'bind';
				$pre_tag = PHP_START.' while('.$this->getVariable($this->attributes['bind-as']).' = $this->'.$this->attributes['binding'].'()) { '.PHP_STOP;
				$post_tag = PHP_START.' } '.PHP_STOP;
				$this->attributes['binding'] = $this->attributes['bind-as'] = null;
				}

			if($this->attributes['foreach']) {
				if(!$this->attributes['each-as']) $this->attributes['each-as'] = 'each';
				$pre_tag = PHP_START.' if('.$this->getVariable($this->attributes['foreach']).') foreach ('.$this->getVariable($this->attributes['foreach']).' as '.$this->getVariable($this->attributes['each-as']).') { '.PHP_STOP;
				$post_tag = PHP_START.' } '.PHP_STOP;
				$this->attributes['foreach'] = $this->attributes['each-as'] = null;
				}

			if($this->attributes['snipet']) {
				$save_as = $this->attributes['snipet'];
				$this->attributes['snipet'] = null;
				if($this->attributes['view'] == 'false') $noview = true;
				$this->attributes['view'] = null;
				}

			if($this->attributes['data-container']) {
				$env['data-container'] = $this->attributes['data-container'];
				$this->attributes['data-container'] = null;
				}

			if($this->attributes['data-source']) {
				$env['data-source'] = $this->attributes['data-source'];
				// $this->attributes['data-source'] = null;
				}

			if( ($this->attributes['name']) && in_array($this->nodeName, array('input', 'textarea')) && ($env['data-container']) )
				$this->attributes['name'] = $env['data-container'].'['.$this->attributes['name'].']';

			if($this->attributes['x3']) {
				$pre_sub = '<input type="hidden" name="formID" value="'.$this->attributes['x3'].'" />';
				$this->attributes['x3'] = null;
				}

			$out.= $pre_tag;


			if($this->nodeName == 'script') $sub = $this->getChildContents($env);
				else $sub = $this->getChildNodes($env);


			if(!$notag) {
	                       	$out.= '<'.$this->nodeName.$this->getAttributes();
				if($this->nodeType == NODE_PAIR) $out.= '>'.$pre_sub.$sub.$post_sub.'</'.$this->nodeName.'>';
					else $out.=' />';
				} else {
					$out.= $pre_sub.$sub.$post_sub;
      				}
			$out.= $post_tag;
			}
		$sub = null;
		if($save_as) file_put_contents(System::$TemplateOutput.$save_as.'.php', $out);
        // $save_as - pokud je aktivní, uloží aktuální kus zkompilované šablony - použito tušímže pouze u atributu snipet
		if($noview) $out = '';
		return($out);
		}

/*
	private function include_renderer() {
		$this->setAttribute('notag', 1);
		$this->pre_tag = PHP_START.' include(\''.$this->getAttribute('src').'.php\'); '.PHP_STOP;
		}
*/

	private function textboxTransformer($e) {
		$this->nodeName = 'input';
		$this->setAttribute('type', 'text');
		if($e['data-source']) $this->setAttribute('value', PHP_ECHO_START.$this->getVariable($e['data-source'].'.'.$this->attributes['name']).PHP_ECHO_STOP);
		$this->nodeType = NODE_SINGLE;
		}

	private function passwordTransformer($e) {
		$this->nodeName = 'input';
		$this->setAttribute('type', 'password');
		if($e['data-source']) $this->setAttribute('value', PHP_ECHO_START.$this->getVariable($e['data-source'].'.'.$this->attributes['name']).PHP_ECHO_STOP);
		$this->nodeType = NODE_SINGLE;
		}

	private function checkboxTransformer($e) {
		$this->nodeName = 'input';
		$this->setAttribute('type', 'checkbox');
		$value = $this->getAttribute('value');
		if($e['data-source']) $this->setAttribute('checked', PHP_ECHO_START.'('.$this->getVariable($e['data-source'].'.'.$this->attributes['name']).($value ? '=='.$value : '').'?\'true\':\'false\')'.PHP_ECHO_STOP);
		$this->nodeType = NODE_SINGLE;
		}



	}



class Template {

	public $templateName = null;
	public $isReady = false;

	// config singletag - jednoduche tagy
	private $singletag = array(
		'meta', 'img', 'input', 'br', 'hr', 'link', 'base', // html tagy
		'include', // template tagy
		'textbox', 'checkbox', 'password', // input tagy
	);

	// config odstraneni vicenasobnych mezer
	private $removeSpaces = false;



	public function __construct($templateName) {
		$this->templateName = $templateName;
		if(file_exists(TemplateSource.$templateName.'.html')) {
			if(!file_exists(TemplateOutput.$this->templateName.'.php') || (filemtime(TemplateSource.$templateName.'.html') > @filemtime(TemplateOutput.$this->templateName.'.php'))) {
				$this->Compile();
				$this->Save();
			}
			$this->isReady = true;
		}
	}



	public function Get() {
		return file_get_contents(TemplateOutput.$this->templateName.'.php');
		}


	public function __toString() {
		ob_start();
		include(TemplateOutput.$this->templateName.'.php');
		return ob_get_clean();
		}



	private function Compile($source = null) {

		if(!$source) $source = $this->templateName;

		$html = file_get_contents(TemplateSource.$source.'.html');
		$regexp = '/(<(\/?).+\/?'.'>)/imuU';

		$result = preg_split($regexp, $html, -1, PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY);

		$parentNode = null;
		$rootNode = new Node('HTMLDOCUMENT', null, NODE_ROOT);
		$currentNode = $rootNode;

		foreach($result as $word) {
			// $node = array();
			$is_tag = preg_match('/<(\/?)(\w+)(\s*)(.*)>/u', $word, $matches);
			if($is_tag) {
				$atr = null;
				$tag = $matches[2];
				$is_single_tag = (substr($matches[4], -1) == '/');
				if($is_single_tag || in_array($matches[2], $this->singletag)) $nodeType = NODE_SINGLE; else $nodeType = NODE_PAIR;
				if($matches[4]) {
					preg_match_all('/([\w\S]+)=\"([^\"]*)\"/u', $matches[4], $attrs, PREG_SET_ORDER);
					foreach($attrs as $atrarr) $atr[$atrarr[1]] = $atrarr[2];
					}
				if($matches[1]) { // end tag
					$currentNode = $currentNode->parentNode;
					} else {
					if($nodeType == NODE_SINGLE) {
						$currentNode->appendChild(new Node($tag, null, $nodeType, $currentNode, $atr, $matches[0]));
						} else {
						$node = new Node($tag, null, $nodeType, $currentNode, $atr, $matches[0]);
						$currentNode->appendChild($node);
						$currentNode = $node;
						}
					}
				} elseif(!is_object($currentNode)) {
//					Errorhandling::Critical(E_PARSE, 'X3/Template Engine Error: Pair element without start tag! Check template syntax', TemplateSource.$this->templateName.'.html', 1, null);
					trigger_error('X3/Template Engine Error: End tag &lt;/'.$tag.'&gt;without start tag! Check template syntax', E_USER_ERROR);
				} else $currentNode->appendChild(new Node(null, $word, NODE_TEXT, $currentNode, null, $matches[0]));

			}

		$this->templateContent = $rootNode->toString();
	}


	private function Save($name = null) {
		if(!$name) $name = $this->templateName;
		if($this->removeSpaces) $this->templateContent = preg_replace(array('/(>\s+<)/', '/(\s+)/'), array('><', ' '), $this->templateContent);
		file_put_contents(TemplateOutput.$name.'.php', $this->templateContent);
		}

}
