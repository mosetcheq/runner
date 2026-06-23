<?php

namespace Rnr\Http;

class Session {

	public function __construct(?string $ses_id = null)
    {
		if($ses_id) session_name($ses_id);
		if(session_status() === PHP_SESSION_NONE) session_start();
	}



    public function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }



	public function __set(string $key, mixed $val) : void
    {
		$_SESSION[$key] = $val;
	}


	public function __get(string $key) : mixed
    {
		return $_SESSION[$key] ?? null;
	}


    public function __isset(string $key): bool
    {
        return isset($_SESSION[$key]);
    }


    public function __unset(string $key): void
    {
        unset($_SESSION[$key]);
    }


	public function destroy() : bool
    {
        $_SESSION = [];

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
        return session_destroy();
	}

}