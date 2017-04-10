<?php

use Rnr\DB,
    Rnr\Output,
    Rnr\Session;

abstract class Rnr {

	public function __construct() {

		if(defined('DB_host')) {
			DB::connect(DB_host, DB_user, DB_password, DB_name);
		}

		$this->view = new Output();
		$this->session = new Session();

	}

}
