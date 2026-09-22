<?php

namespace App\Exceptions;

use App\Support\ClientIdentifier;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Facades\Log;
use Throwable;
use Webling\API\ClientException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * @param Throwable $exception
     * @return void
     */
    public function report(Throwable $exception)
    {
        if (config('app.client_logging', false) && $this->shouldReport($exception)) {
            if ($this->logExceptionWithClientInfo($exception)) {
                return;
            }
        }

        parent::report($exception);
    }

    /**
     * Log an exception with client information from the bearer token.
     *
     * @param Throwable $exception
     * @return bool Whether the exception was logged with client info
     */
    protected function logExceptionWithClientInfo(Throwable $exception): bool
    {
        $oauthClientId = ClientIdentifier::getClientId();

        if ($oauthClientId === null) {
            return false;
        }

        Log::error($exception->getMessage(), [
            'client_id' => $oauthClientId,
            'exception' => $exception,
        ]);
        return true;
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param \Illuminate\Http\Request $request
     * @param Throwable $exception
     * @return \Illuminate\Http\Response
     */
    public function render($request, Throwable $exception)
    {
        if ($exception instanceof ClientException) {
            abort(400, "Exception: " . $exception->getMessage());
        }
        return parent::render($request, $exception);
    }
}
