<?php

class Application extends Rnr {

	public function Main($data = null) {

		$this->view->pagetitle = $this->view->heading = 'Hello world!';
		$this->view->paragraph = 'Request: '.print_r($data, true);

		return Template('index');
	}

	public function onMyformSubmit($formdata) {
		return JSON($formdata->GetAll());
	}
}
