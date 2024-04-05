<?php
/**
 * IPP - Value exception
 * @author Martin Slezák - xsleza26
 */

namespace IPP\Student\Exception;

use IPP\Core\Exception\IPPException;
use IPP\Core\ReturnCode;

/**
 * Exception for value
 */
class ValueException extends IPPException {
    public function __construct(
        string $message = "Missing value")
    {
        parent::__construct(
            $message, ReturnCode::VALUE_ERROR, null, false
        );
    }
}
