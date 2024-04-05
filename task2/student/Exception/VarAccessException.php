<?php
/**
 * IPP - Variable access exception
 * @author Martin Slezák - xsleza26
 */

namespace IPP\Student\Exception;

use IPP\Core\Exception\IPPException;
use IPP\Core\ReturnCode;

/**
 * Exception for variable access
 */
class VarAccessException extends IPPException {
    public function __construct(
        string $message = "Variable access exception")
    {
        parent::__construct(
            $message, ReturnCode::VARIABLE_ACCESS_ERROR, null, false
        );
    }
}
