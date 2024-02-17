<?php
/**
 * IPP - Frame Access Exception
 * @author Martin Slezák - xsleza26
 */

namespace IPP\Student\Exception;

use IPP\Core\Exception\IPPException;
use IPP\Core\ReturnCode;

/**
 * Exception for frame access error
 */
class FrameAccessException extends IPPException {
    public function __construct(string $message = "Frame access error") {
        parent::__construct(
            $message, ReturnCode::FRAME_ACCESS_ERROR, null, false
        );
    }
}
