<?php

class Iquest_auth_result
{

    // General Failure
    const FAILURE                        =  0;

    // Failure due to identity not being found.
    const FAILURE_IDENTITY_NOT_FOUND     = -1;

    // Failure due to identity being ambiguous.
    const FAILURE_IDENTITY_AMBIGUOUS     = -2;

    // Failure due to invalid credential being supplied.
    const FAILURE_CREDENTIAL_INVALID     = -3;

    // Failure due to no credential being supplied.
    const FAILURE_CREDENTIAL_NOT_PROVIDED= -4;

    // Failure due to uncategorized reasons.
    const FAILURE_UNCATEGORIZED          = -5;

    // Authentication success.
    const SUCCESS                        =  1;


    protected $code;
    protected $messages;

    public function __construct($code, $messages = array()){

        if (!is_array($messages)) $messages = array($messages);

        $this->code     = (int)$code;
        $this->messages = $messages;
    }

    public function isValid(){
        return ($this->code > 0) ? true : false;
    }

    public function getCode() { return $this->code; }
    public function getMessages() { return $this->messages; }

    public function getCodeAsStr(){
        switch($this->code){
            case  0: return 'FAILURE';
            case -1: return 'FAILURE_IDENTITY_NOT_FOUND';
            case -2: return 'FAILURE_IDENTITY_AMBIGUOUS';
            case -3: return 'FAILURE_CREDENTIAL_INVALID';
            case -4: return 'FAILURE_CREDENTIAL_NOT_PROVIDED';
            case -5: return 'FAILURE_UNCATEGORIZED';
            case  1: return 'SUCCESS';
            default: return 'UNKNOWN:'.$this->code;
        }
    }
}
