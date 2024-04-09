<?php
/**
 * IPP - Exit exception
 * @author Martin Slezák - xsleza26
 */

namespace IPP\Student\Exception;

use IPP\Core\Exception\IPPException;
use IPP\Core\ReturnCode;

/**
 * Exception for exiting with given code
 */
class ExitException extends IPPException {
    public int $ret_value;

    public function __construct(
        int $ret_value,
        string $message = "Exit program"
    ) {
        $this->ret_value = $ret_value;
        parent::__construct(
            $message, ReturnCode::FRAME_ACCESS_ERROR, null, false
        );
    }
}
