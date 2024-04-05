<?php
/**
 * IPP - XML invalid source structure exception
 * @author Martin Slezák - xsleza26
 */

namespace IPP\Student\Exception;

use IPP\Core\Exception\IPPException;
use IPP\Core\ReturnCode;

/**
 * Exception for XML invalid source structure exception
 */
class XMLStructException extends IPPException {
    public function __construct(
        string $message = "Invalid source XML structure")
    {
        parent::__construct(
            $message, ReturnCode::INVALID_SOURCE_STRUCTURE, null, false
        );
    }
}
