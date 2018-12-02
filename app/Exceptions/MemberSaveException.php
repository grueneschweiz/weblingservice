<?php
/**
 * Created by PhpStorm.
 * User: cyrillbolliger
 * Date: 27.10.18
 * Time: 16:55
 */

namespace App\Exceptions;


class MemberSaveException extends \Exception {

  //TODO this Exception is not thrown yet (24.11. anss)
  /**
   * Render the exception into an HTTP response.
   *
   * @param  \Illuminate\Http\Request
   * @return \Illuminate\Http\Response
   */
  public function render($request) {
      abort(500, "Could not save Member.");
  }
}
