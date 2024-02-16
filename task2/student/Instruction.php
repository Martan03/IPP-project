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
    private string $opcode;
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
            "LT" => $this->cmp($storage, "lt"),
            "GT" => $this->gt($storage, "gt"),
            "EQ" => $this->eq($storage, "eq"),
            "AND" => $this->and($storage),
            "OR" => $this->or($storage),
            "NOT" => $this->not($storage),
            "INT2CHAR" => $this->int2char($storage),
            "STRI2INT" => $this->stri2int($storage),
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
        $item = $this->getSymb($storage, $this->args[1]);

        list($frame, $name) = explode('@', $this->args[0]->getValue());
        $storage->add($frame, $name, $item->getType(), $item->getValue());
    }

    private function defVar(Storage $storage) {
        list($frame, $name) = explode('@', $this->args[0]->getValue());
        // TODO: use correct exception
        if (!$storage->defVar($frame, $name))
            throw new ParameterException();
    }

    private function calc(Storage $storage) {
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

        list($frame, $name) = explode('@', $this->args[0]->getValue());
        $storage->add($frame, $name, "int", $res);
    }

    private function cmp(Storage $storage, callable $cmpFun) {
        $item1 = $this->getSymb($storage, $this->args[1]);
        $item2 = $this->getSymb($storage, $this->args[2]);
        // TODO return code
        if ($item1->getType() != $item2->getType())
            return;

        $res = call_user_func_array(
            array($this, $cmpFun),
            array($item1->getValue(), $item2->getValue())
        );
        list($frame, $name) = explode('@', $this->args[0]->getValue());
        $storage->add($frame, $name, "bool", $res);
    }

    private function and(Storage $storage) {
        $item1 = $this->getSymb($storage, $this->args[1]);
        $item2 = $this->getSymb($storage, $this->args[2]);
        // TODO return code
        if ($item1->getType() != "bool" || $item2->getType() != "bool")
            return;

        $res = $item1->getValue() && $item2->getValue();
        list($frame, $name) = explode('@', $this->args[0]->getValue());
        $storage->add($frame, $name, "bool", $res);
    }

    private function or(Storage $storage) {
        $item1 = $this->getSymb($storage, $this->args[1]);
        $item2 = $this->getSymb($storage, $this->args[2]);
        // TODO return code
        if ($item1->getType() != "bool" || $item2->getType() != "bool")
            return;

        $res = $item1->getValue() || $item2->getValue();
        list($frame, $name) = explode('@', $this->args[0]->getValue());
        $storage->add($frame, $name, "bool", $res);
    }

    private function not(Storage $storage) {
        $item = $this->getSymb($storage, $this->args[1]);
        if ($item->getType() != "bool")
            return;

        list($frame, $name) = explode('@', $this->args[0]->getValue());
        $storage->add($frame, $name, "bool", !$item->getValue());
    }

    private function int2char(Storage $storage) {
        $item = $this->getSymb($storage, $this->args[1]);
        if ($item->getType() != "int")
            return;

        list($frame, $name) = explode('@', $this->args[0]->getValue());
        $storage->add($frame, $name, "string", chr($item->getValue()));
    }

    private function stri2int(Storage $storage) {
        $item1 = $this->getSymb($storage, $this->args[1]);
        $item2 = $this->getSymb($storage, $this->args[2]);
        if ($item1->getType() != "string" || $item2->getType() != "int")
            return;

        $res = ord($item1->getValue()[$item2->getValue()]);
        list($frame, $name) = explode('@', $this->args[0]->getValue());
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


    private function lt(mixed $val1, mixed $val2): bool {
        return $val1 < $val2;
    }

    private function gt(mixed $val1, mixed $val2): bool {
        return $val1 > $val2;
    }

    private function eq(mixed $val1, mixed $val2): bool {
        return $val1 === $val2;
    }
}
