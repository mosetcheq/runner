<?php

class Documents extends Rnr {

	public function Index($data = null) {

		$this->view->pagetitle = $this->view->heading = 'Hello world!';
		$this->view->paragraph = 'Request: '.print_r($data, true);

		return Template('index');

	}

}
