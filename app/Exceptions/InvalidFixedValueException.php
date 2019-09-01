<?php
/**
 * Created by PhpStorm.
 * User: cyrillbolliger
 * Date: 14.10.18
 * Time: 22:56
 */

namespace App\Exceptions;


class InvalidFixedValueException extends \Exception
{
    
    /**
     * Render the exception into an HTTP response.
     *
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function render($request)
    {
        abort(500, "Internal Server Error: " . $this->getMessage());
    }
}
