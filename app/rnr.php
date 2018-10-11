<?php

use Rnr\DB,
    Rnr\Output,
    Rnr\Session,
    Rnr\FormHandler;

abstract class Rnr {

	public function __construct() {

		if(defined('DB_host')) {
			DB::connect(DB_host, DB_user, DB_password, DB_name);
		}

		$this->view = new Output();
		$this->session = new Session('s');
	}

	
	public function onLoad() {
		$form = new FormHandler;
		return $form->CallHandler($this);
	}


	/**
 * User defined template engine
 */
/*
 	public function Template($template_name) {
		$this->view->contentType = 'text/plain';
		$this->view->charset = 'utf-8';
		ob_start();
		echo("Template name: {$template_name}\nAssigned variables:\n");
		print_r($this->view->GetAssigned());
		return ob_get_clean();
	}
*/
/*
	public function BeforeRender() {
	}

	public function AfterRender() {
	}

	public function OutputProcessing($output) {
		return $output;
	}
*/
}
