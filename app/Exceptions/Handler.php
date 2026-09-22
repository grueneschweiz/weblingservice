<?php

namespace App\Exceptions;

use App\Support\ClientIdentifier;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
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

    protected function context()
    {
        $context = parent::context();

        if (config('app.client_logging', false)) {
            $oauthClientId = ClientIdentifier::getClientId();
            if ($oauthClientId !== null) {
                $context['client_id'] = $oauthClientId;
            }
        }

        return $context;
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
