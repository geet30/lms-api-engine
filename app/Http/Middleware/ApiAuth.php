<?php

namespace App\Http\Middleware;

use Closure;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\AffiliateKeys;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\JWTException;

class ApiAuth
{

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        try {
            $apiKey = $request->header('api-key');
            if (!$apiKey) {
                return errorResponse('API-Key is required', 422, TOKEN_VALIDATION_CODE);
            }

            $user = $this->verifyToken($request);
            if (AffiliateKeys::isKeyExist($user->id, $apiKey)) {
                return $next($request);
            }
            return errorResponse("Affiliate having given token doesn't have any correct API-key.", 422, TOKEN_VALIDATION_CODE);
        } catch (TokenExpiredException $e) {
            try {
                $refreshedToken = JWTAuth::refresh();
                JWTAuth::setToken($refreshedToken);
                return errorResponse($e->getMessage(), $e->getCode(), EXPIRED_ERROR_CODE);
            } catch (JWTException $e) {
                return errorResponse($e->getMessage(), $e->getCode(), TOKEN_ERROR_CODE);
            }
        } catch (TokenInvalidException $e) {
            return errorResponse('Token is invalid. Please pass valid Token.', $e->getCode(), TOKEN_ERROR_CODE);
        } catch (JWTException $e) {
            return errorResponse($e->getMessage(), $e->getCode(), TOKEN_ERROR_CODE);
        }
    }

     /**
     * Verify Token and fetch user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    private function verifyToken($request)
    {
        $token = $request->header('Auth-Token');
        $request->headers->remove('Auth-Token');
        $request->headers->set('Authorization', 'Bearer ' . $token);
        return JWTAuth::parseToken()->authenticate();
    }
}
