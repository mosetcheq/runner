<?php
namespace Rnr\Utils;

class StopWatch {

	// start time
	private ?float $start = null;

	// constructor
	public function __construct() {
		$this->start = microtime(true);
	}

    public function elapsed() : float {
        return (microtime(true) - $this->start) * 1000;
    }

	// get delta time
	public function __toString() : string
    {
		return (string)round($this->elapsed(), 3);
	}

}
