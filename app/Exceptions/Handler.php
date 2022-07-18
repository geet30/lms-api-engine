<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Laravel\Lumen\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\{NotFoundHttpException, HttpException, MethodNotAllowedHttpException};
use Throwable;
use App\Traits\Email\Exception;

class Handler extends ExceptionHandler
{
    use Exception;

    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        AuthorizationException::class,
        HttpException::class,
        ModelNotFoundException::class,
        ValidationException::class,
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Throwable  $exception
     * @return void
     *
     * @throws \Exception
     */
    public function report(Throwable $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $exception
     * @return \Symfony\Component\HttpFoundation\Response|\Illuminate\Http\JsonResponse
     *
     * @throws \Throwable
     */
    public function render($request, Throwable $exception)
    {
        if (config('env.EXCEPTION_ENABLED')) {
            $sendException = true;
            if ($exception instanceof ModelNotFoundException) {
                $sendException = false;
            } elseif ($exception instanceof \PDOException) {
                $sendException = false;
            } elseif ($exception instanceof MethodNotAllowedHttpException || $exception instanceof NotFoundHttpException) {
                $sendException = false;
            }
            if ($sendException) {
                $this->sendExceptionAlert($exception->getMessage() . " in file :" . $exception->getFile() . " on line :" . $exception->getLine(), __FUNCTION__);
            }
        }
        return errorResponse($exception->getMessage() . " in file :" . $exception->getFile() . " on line :" . $exception->getLine(), HTTP_STATUS_SERVER_ERROR, HTTP_STATUS_SERVER_ERROR);
    }
}
