<?php

namespace App\Exceptions;
use Exception;
use Throwable;

class ServerException extends Exception
{
    /**
     * Report the exception.
     *
     * @return void
     */
    public function report() {
        //
    }

    /**
     * Render the exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function render($request) {
        return response()->json(['message' => $this->getMessage(), 'status' => 500], 500);
    }
}
