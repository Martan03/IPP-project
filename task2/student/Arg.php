<?php
/**
 * IPP - Argument class
 * @author Martin Slezák - xsleza26
 */

namespace IPP\Student;

/**
 * Class that defines instruction argument
 */
class Arg {
    private string $type;
    private string $value;

    /**
     * Constructs new argument with given type and value
     */
    public function __construct(string $type, string $value) {
        $this->type = $type;
        $this->value = $value;
    }

    /**
     * Gets arguments type
     */
    public function getType(): string {
        return $this->type;
    }

    /**
     * Gets arguments value
     */
    public function getValue(): string {
        return $this->value;
    }
}
