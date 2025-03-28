<?php

namespace fayyaztech\PhonePeGatewayPGV2;

use Exception;

class PhonePeApiException extends Exception
{
    public function errorMessage(): string
    {
        return "Error on line {$this->getLine()} in {$this->getFile()}. {$this->getMessage()}.";
    }
}
