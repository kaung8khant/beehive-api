<?php

namespace App\Helpers;

trait ResponseHelper
{
    protected function generateResponse($data, $status, $error = FALSE)
    {
        $response['status'] = $status;

        if ($error) {
            $response['message'] = $data;
        } else {
            $response['data'] = $data;
        }

        return response()->json($response, $status);
    }
}
