<?php
/**
 * Created by PhpStorm.
 * User: cyrillbolliger
 * Date: 14.10.18
 * Time: 22:56
 */

namespace App\Exceptions;


class RevisionNotFoundException extends \Exception
{
    
    /**
     * Render the exception into an HTTP response.
     *
     * @param \Illuminate\Http\Request
     */
    public function render($request)
    {
        abort(404, "Revision does not exist.");
    }
}
