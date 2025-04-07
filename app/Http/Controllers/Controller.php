<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function successResponse($data = [], $message = null)
    {
        $response = array('success' => true);
        if (!empty($message)) {
            $response['message'] = $message;
        }
        if($data){
            $response['data'] = $data;
        }
        return response()->json($response, 200);
    }

    public function errorResponse($message, $code = 404)
    {

        $response = array('success' => false, 'message' => $message);
        return response()->json($response, $code);
    }
}
