<?php
/**
 * IPP - Operand Value Exception
 * @author Martin Slezák - xsleza26
 */

namespace IPP\Student\Exception;

use IPP\Core\Exception\IPPException;
use IPP\Core\ReturnCode;

/**
 * Exception for operand value error
 */
class OperandValueException extends IPPException {
    public function __construct(string $message = "Operand value error") {
        parent::__construct(
            $message, ReturnCode::OPERAND_VALUE_ERROR, null, false
        );
    }
}
