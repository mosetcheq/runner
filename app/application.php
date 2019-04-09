<?php

class Application extends Rnr {

	public function Main($data = null): Response {

		$this->view->pagetitle = $this->view->heading = 'Hello world!';
		$this->view->paragraph = 'Request: '.print_r($data, true);

		return Template('index');
	}

	public function onMyformSubmit($formdata): Response {
		return JSON($formdata->GetAll());
	}
}
