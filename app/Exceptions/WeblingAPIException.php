<?php
/**
 * Created by PhpStorm.
 * User: cyrillbolliger
 * Date: 27.10.18
 * Time: 16:55
 */

namespace App\Exceptions;


class WeblingAPIException extends \Exception
{
    
    /**
     * Render the exception into an HTTP response.
     *
     * @param \Illuminate\Http\Request
     *
     * @return \Illuminate\Http\Response
     */
    public function render($request)
    {
        abort($this->getCode(), "Remote API Error: " . $this->getMessage());
    }
}
