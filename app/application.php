<?php
use PHPMailer\PHPMailer\PHPMailer as PHPMailer;
use PHPOffice\PhpSpreadsheet\Spreadsheet;

class Application extends Rnr {

	public function Main($data = null): Response {

		$mail = new PHPMailer();
		
		$this->view->pagetitle = $this->view->heading = 'Hello world!';
		$this->view->paragraph = 'Request: '.print_r($data, true);

		return Response::Template('index', $this->view);
	}

	public function onMyformSubmit($formdata): Response {
		return Response::JSON($formdata->GetAll())->responseCode(403);
	}
}
