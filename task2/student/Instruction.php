<?php
/**
 * IPP - Instruction class
 * @author Martin Slezák - xsleza26
 */

namespace IPP\Student;

use IPP\Core\Exception\ParameterException;

/**
 * Defines instruction with its opcode and arguments
 */
class Instruction {
    public string $opcode;
    private array $args;

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

    public function execute(Storage $storage) {
        match ($this->opcode) {
            "MOVE" => $this->move($storage),
            "CREATEFRAME" => $storage->create(),
            "PUSHFRAME" => $storage->push(),
            "POPFRAME" => $storage->pop(),
            "DEFVAR" => $this->defVar($storage),
            "CALL" => $this->none(),
            "RETURN" => $this->none(),
            "PUSHS" => $this->none(),
            "POPS" => $this->none(),
            "ADD", "SUB", "MUL", "IDIV" => $this->calc($storage),
            "LT" => $this->none(),
            "GT" => $this->none(),
            "EQ" => $this->none(),
            "AND" => $this->none(),
            "OR" => $this->none(),
            "NOT" => $this->none(),
            "INT2CHAR" => $this->none(),
            "STRI2INT" => $this->none(),
            "READ" => $this->none(),
            "WRITE" => $this->none(),
            "CONCAT" => $this->none(),
            "STRLEN" => $this->none(),
            "GETCHAR" => $this->none(),
            "SETCHAR" => $this->none(),
            "TYPE" => $this->none(),
            "LABEL" => $this->none(),
            "JUMP" => $this->none(),
            "JUMPIFEQ" => $this->none(),
            "JUMPIFNEQ" => $this->none(),
            "EXIT" => $this->none(),
            "DPRINT" => $this->none(),
            "BREAK" => $this->none(),
            default => $this->none(),
        };
    }

    private function none() {}

    private function move(Storage $storage) {
        list($frame, $name) = explode('@', $this->args[0]->getValue());

        $item = $this->getSymb($storage, $this->args[1]);
        $storage->add($frame, $name, $item->getType(), $item->getValue());
    }

    private function defVar(Storage $storage) {
        list($frame, $name) = explode('@', $this->args[0]->getValue());
        // TODO: use correct exception
        if (!$storage->defVar($frame, $name))
            throw new ParameterException();
    }

    private function calc(Storage $storage) {
        list($frame, $name) = explode('@', $this->args[0]->getValue());

        $item1 = $this->getSymb($storage, $this->args[1]);
        // TODO: use correct exception
        if ($item1->getType() != 'int')
            throw new ParameterException();

        $item2 = $this->getSymb($storage, $this->args[2]);
        // TODO: use correct exception
        if ($item2->getType() != 'int')
            throw new ParameterException();

        $res = match ($this->opcode) {
            "ADD" => (int)$item1->getValue() + (int)$item2->getValue(),
            "SUB" => (int)$item1->getValue() - (int)$item2->getValue(),
            "MUL" => (int)$item1->getValue() * (int)$item2->getValue(),
            "IDIV" => (int)$item1->getValue() / (int)$item2->getValue(),
        };

        $storage->add($frame, $name, "int", $res);
    }


    private function getSymb(Storage $storage, Arg $arg): StorageItem {
        $type = $arg->getType();
        $value = $arg->getValue();
        $item = new StorageItem($type, $value);
        if ($type == "var") {
            list($fr, $n) = explode('@', $value);
            $item = $storage->get($fr, $n);
            $type = $item->getType();
            $value = $item->getValue();
        }

        return $item;
    }
}
