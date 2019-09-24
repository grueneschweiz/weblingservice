<?php

namespace App\Exceptions;


class BadRequestException extends \Exception
{
    
    /**
     * Render the exception into an HTTP response.
     *
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function render($request)
    {
        abort(400, "Bad Request: " . $this->getMessage());
    }
}
