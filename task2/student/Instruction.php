<?php
/**
 * IPP - Instruction class
 * @author Martin Slezák - xsleza26
 */

namespace IPP\Student;

/**
 * Defines instruction with its opcode and arguments
 */
class Instruction {
    public string $opcode;
    public array $args;

    /**
     * Constructs new instruction with given opcode and empty args
     */
    public function __construct(string $opcode) {
        $this->opcode = $opcode;
        $this->args = [];
    }

    /**
     * Sets instruction args to given array
     */
    public function setArgs(array $args) {
        $this->args = $args;
    }

    /**
     * Adds given argument to instruction arguments
     */
    public function addArg(Arg $arg) {
        $this->args[] = $arg;
    }
}
