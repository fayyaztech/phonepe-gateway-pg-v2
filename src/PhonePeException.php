<?php

namespace fayyaztech\phonePePaymentGateway;

use Exception;

class PhonePeApiException extends \Exception
{
    public function errorMessage(): string
    {
        return "Error on line {$this->getLine()} in {$this->getFile()}. {$this->getMessage()}.";
    }
}

// Then modify error handling:
if ($err) {
    throw new PhonePeApiException("API Call Failed: {$err}");
}
