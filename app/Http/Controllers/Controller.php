<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * @param int $status
     * @param array $data
     * @param array $messages
     * @param bool $validation
     * @return \Illuminate\Http\JsonResponse
     */
    public function FailResponse($data = [], $messages=["Failed"], $status=500, $custom_code=500, $validation = false)
    {
        $errors = $messages;

        if($validation){
            $errors = [];
            foreach ($messages->errors() as $key => $messages){
                $errors[] = $messages[0];
            }
        }

        return response()->json(
            [
                'code' => $custom_code != 500 ? (int)$custom_code : (int)$status,
                'messages' => $errors,
                'data' => $data
            ], $status
        )->header('status', $status);
    }

    /**
     * @param int $status
     * @param array $data
     * @param array $messages
     * @return \Illuminate\Http\JsonResponse
     */
    public function SuccessResponse($data=[], $messages=["Success"], $status=200, $custom_code=200)
    {
        return response()->json(
            [
                'code' => $custom_code != 200 ? (int)$custom_code : (int)$status,
                'messages' => $messages,
                'data' => $data
            ], $status
        )->header('status', $status);
    }
}
