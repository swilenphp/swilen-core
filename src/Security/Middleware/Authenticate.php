<?php

namespace Swilen\Security\Middleware;

use Swilen\Http\Exception\HttpForbiddenException;
use Swilen\Http\Exception\HttpUnauthorizedException;
use Swilen\Http\Request;
use Swilen\Security\Exception\JwtFatalException;
use Swilen\Security\Jwt\Payload;

class Authenticate
{
    /**
     * Handle incoming request for authenticate user.
     *
     * @param \Swilen\Http\Request $request
     * @param \Closure             $next
     *
     * @return \Closure
     */
    public function handle(Request $request, \Closure $next)
    {
        $rawToken = $request->bearerToken();
        if ($rawToken === null || empty($rawToken)) {
            throw new HttpForbiddenException();
        }

        try {
            $data = $this->isAuthenticated($rawToken);
        } catch (\Throwable $th) {
            if ($th instanceof HttpUnauthorizedException) {
                throw $th;
            }
            if ($th instanceof JwtFatalException) {
                throw $th;
            }

            throw new HttpUnauthorizedException();
        }

        return $next($request->withUser($data->data()));
    }

    /**
     * Verify if user is authenticated.
     *
     * @param string $token
     *
     * @return \Swilen\Security\Jwt\Payload
     */
    protected function isAuthenticated(string $token): Payload
    {
        return new Payload(['data' => 'name', 'token' => $token]);
    }
}
