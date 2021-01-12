<?php

namespace App\Http\Middleware;

use Closure;
use Session;

class CheckSessions {
	public function handle( $request, Closure $next ) {
		if( !session()->has( 'IS_LOGIN' ) ) {
			return redirect( 'login' );
		}

		return $next( $request );
	}
}