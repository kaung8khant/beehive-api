<?php

namespace App\Helpers;

trait ResponseHelper
{
    protected function generateResponse($data, $status, $message = FALSE)
    {
        $response['status'] = $status;

        if ($message) {
            $response['message'] = $data;
        } else {
            $response['data'] = $data;
        }

        return response()->json($response, $status);
    }
}
