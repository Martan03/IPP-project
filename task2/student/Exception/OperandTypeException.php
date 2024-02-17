<?php
/**
 * IPP - Operand Type Exception
 * @author Martin Slezák - xsleza26
 */

namespace IPP\Student\Exception;

use IPP\Core\Exception\IPPException;
use IPP\Core\ReturnCode;

/**
 * Exception for operand type error
 */
class OperandTypeException extends IPPException {
    public function __construct(string $message = "Operand type error") {
        parent::__construct(
            $message, ReturnCode::OPERAND_TYPE_ERROR, null, false
        );
    }
}
