<?php

namespace Rnr;

class ErrorDocument {

	public function onLoad() {
		return ErrorDocument(404);
	}

	public function __call($func, $params) {
		return ErrorDocument(404);
	}

}