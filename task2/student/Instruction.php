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
    /** @var array<int, Arg> arguments array */
    public array $args;

    /**
     * Constructs new instruction with given opcode and empty args
     * @param string $opcode Operation code of the instruction
     */
    public function __construct(string $opcode) {
        $this->opcode = $opcode;
        $this->args = [];
    }

    /**
     * Sets instruction args to given array
     * @param array<int, Arg> $args arguments to set instruction arguments to
     */
    public function setArgs(array $args): void {
        $this->args = $args;
    }

    /**
     * Adds given argument to instruction arguments
     * @param Arg $arg argument to add to instruction arguments
     */
    public function addArg(Arg $arg): void {
        $this->args[] = $arg;
    }
}
