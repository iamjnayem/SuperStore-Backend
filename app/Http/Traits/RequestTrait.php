<?php
namespace App\Http\Traits;

use App\Models\User;
use Illuminate\Support\Facades\Log;

trait RequestTrait
{
    public $request;
    public $user_id;
    public $message = [
        'data' => [],
        'messages' => ['Failed'],
        'validation' => false,
        'status' => 500,
        'custom_code' => 500,
    ];
    function setSettings($request)
    {
        $this->request = $request;
    }
    public function configureException($exception)
    {
        $this->message['messages'] = $exception;
        $this->message['validation'] = true;
        $this->message['custom_code'] = 422;
        $this->message['status'] = 422;
    }
    public function configureCustomException($exception)
    {
        $this->message['messages'] = $exception->getMessage();
        $this->message['validation'] = false;
        $this->message['custom_code'] = $exception->getCode() != 0 ? $exception->getCode() :404;
        $this->message['status'] = $exception->getCode() != 0 ? $exception->getCode() :404;
    }
    public function writeErrors($exception)
    {
        Log::error([
            $exception->getFile(),
            $exception->getLine(),
            $exception->getMessage(),
        ]);
    }
}
