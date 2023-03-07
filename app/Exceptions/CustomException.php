<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use JetBrains\PhpStorm\Internal\LanguageLevelTypeAware;
use Throwable;

class CustomException extends Exception
{

    public function __construct(public $errors, public int $status = 500, string $message = "", int $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->setErrors($this->errors);
    }

    /**
     * Render the exception as an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function render($request)
    {
        return Response::make((array)$this->errors, $this->status);
    }


    /**
     * @param $errors
     */
    public function setErrors($errors): void
    {
        if ($errors instanceof CustomException) {
            throw $errors;
        }

        $this->setMessage($this->message);
        //$this->errors = ['message' => $this->message, 'errors' => $errors instanceof Throwable ? [$errors->getMessage()] : $errors];
        $this->errors = ['data' =>[],'messages' => [$this->message], 'code' =>$this->status ];
    }


    /**
     * @param mixed $message
     */
    public function setMessage($message): void
    {
        if (!$message) {
            $this->message = $this->summarizeMessage($this->errors);
        }
    }



    /**
     * @param array|mixed $errors
     * @return mixed|string
     */
    public function summarizeMessage($errors)
    {
        if (is_array($errors)) {
            foreach ($errors as $error) {
                if (is_array($error)) {
                    return $this->summarizeMessage($error);
                }
                return $error;
            }
        }

        return "Something went wrong !";
    }
}
