<?php
/**
 * IPP - Semantic Exception
 * @author Martin Slezák - xsleza26
 */

namespace IPP\Student\Exception;

use IPP\Core\Exception\IPPException;
use IPP\Core\ReturnCode;

/**
 * Exception for semantic error
 */
class SemanticException extends IPPException {
    public function __construct(string $message = "Semantic error") {
        parent::__construct($message, ReturnCode::SEMANTIC_ERROR, null, false);
    }
}
