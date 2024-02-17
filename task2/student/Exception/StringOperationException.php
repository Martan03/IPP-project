<?php
/**
 * IPP - String Operation Exception
 * @author Martin Slezák - xsleza26
 */

namespace IPP\Student\Exception;

use IPP\Core\Exception\IPPException;
use IPP\Core\ReturnCode;

/**
 * Exception for string operation error
 */
class StringOperationException extends IPPException {
    public function __construct(string $message = "String operation error") {
        parent::__construct(
            $message, ReturnCode::STRING_OPERATION_ERROR, null, false
        );
    }
}
