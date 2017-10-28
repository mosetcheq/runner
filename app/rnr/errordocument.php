<?php

namespace Rnr;

class ErrorDocument extends \Rnr{

	public function onLoad() {
		return ErrorDocument(404);
	}

	public function __call($func, $params) {
		return ErrorDocument(404);
	}

}