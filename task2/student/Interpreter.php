<?php

namespace IPP\Student;

use DOMText;
use IntlChar;
use IPP\Core\AbstractInterpreter;
use IPP\Core\Exception\NotImplementedException;
use IPP\Core\Exception\ParameterException;
use IPP\Core\Exception\XMLException;
use IPP\Student\Exception\SemanticException;

class Interpreter extends AbstractInterpreter
{
    private array $instructions;
    private Storage $storage;

    public function execute(): int
    {
        // TODO: Start your code here
        // Check \IPP\Core\AbstractInterpreter for predefined I/O objects:
        // $dom = $this->source->getDOMDocument();
        // $val = $this->input->readString();
        // $this->stdout->writeString("stdout");
        // $this->stderr->writeString("stderr");
        $this->instructions = $this->parseXml();
        $this->storage = new Storage();

        ksort($this->instructions);
        foreach ($this->instructions as $key => $inst) {
            $this->execInstruction($inst, $key);
        }

        echo "\nMemory:\n";
        echo var_dump($this->storage);

        return 0;
    }

    /**
     * Parses input XML and gets array of Instructions
     * @return array array containing Instruction objects
     */
    private function parseXml(): array {
        $instructions = [];
        $dom = $this->source->getDOMDocument();

        foreach ($dom->getElementsByTagName('instruction') as $opcode) {
            $inst = new Instruction($opcode->getAttribute("opcode"));

            foreach ($opcode->childNodes as $arg) {
                if ($arg instanceof DOMText)
                    continue;

                $inst->addArg(new Arg(
                    $arg->getAttribute("type"),
                    $arg->nodeValue,
                ));
            }

            $order = $opcode->getAttribute("order") - 1;
            if ($order < 0 || isset($instructions[$order]))
                throw new XMLException();

            $instructions[$opcode->getAttribute("order") - 1] = $inst;
        }

        return $instructions;
    }

    private function execInstruction(Instruction $inst, int $pos) {
        match ($inst->opcode) {
            "MOVE" => $this->move($inst),
            "CREATEFRAME" => $this->storage->create(),
            "PUSHFRAME" => $this->storage->push(),
            "POPFRAME" => $this->storage->pop(),
            "DEFVAR" => $this->defVar($inst),
            "CALL" => $this->none(),
            "RETURN" => $this->none(),
            "PUSHS" => $this->none(),
            "POPS" => $this->none(),
            "ADD", "SUB", "MUL", "IDIV" => $this->calc($inst),
            "LT" => $this->cmp($inst, "lt"),
            "GT" => $this->gt($inst, "gt"),
            "EQ" => $this->eq($inst, "eq"),
            "AND" => $this->and($inst),
            "OR" => $this->or($inst),
            "NOT" => $this->not($inst),
            "INT2CHAR" => $this->int2char($inst),
            "STRI2INT" => $this->stri2int($inst),
            "READ" => $this->read($inst),
            "WRITE" => $this->write($inst),
            "CONCAT" => $this->concat($inst),
            "STRLEN" => $this->strlen($inst),
            "GETCHAR" => $this->getchar($inst),
            "SETCHAR" => $this->setchar($inst),
            "TYPE" => $this->type($inst),
            "LABEL" => $this->label($inst, $pos),
            "JUMP" => $this->jump($inst),
            "JUMPIFEQ" => $this->jumpifeq($inst),
            "JUMPIFNEQ" => $this->jumpifneq($inst),
            "EXIT" => $this->exit($inst),
            "DPRINT" => $this->dprint($inst),
            "BREAK" => $this->breakInst(),
            default => $this->none(),
        };
    }

    private function none() {}

    private function move(Instruction $inst) {
        $item = $this->getSymb($inst->args[1]);

        list($frame, $name) = explode('@', $inst->args[0]->getValue());
        $this->storage
            ->add($frame, $name, $item->getType(), $item->getValue());
    }

    private function defVar(Instruction $inst) {
        list($frame, $name) = explode('@', $inst->args[0]->getValue());
        if (!$this->storage->defVar($frame, $name))
            throw new SemanticException();
    }

    private function calc(Instruction $inst) {
        $item1 = $this->getSymb($inst->args[1]);
        // TODO: use correct exception
        if ($item1->getType() != 'int')
            throw new ParameterException();

        $item2 = $this->getSymb($inst->args[2]);
        // TODO: use correct exception
        if ($item2->getType() != 'int')
            throw new ParameterException();

        $res = match ($inst->opcode) {
            "ADD" => (int)$item1->getValue() + (int)$item2->getValue(),
            "SUB" => (int)$item1->getValue() - (int)$item2->getValue(),
            "MUL" => (int)$item1->getValue() * (int)$item2->getValue(),
            "IDIV" => (int)$item1->getValue() / (int)$item2->getValue(),
        };

        list($frame, $name) = explode('@', $inst->args[0]->getValue());
        $this->storage->add($frame, $name, "int", $res);
    }

    private function cmp(Instruction $inst, callable $cmpFun) {
        $item1 = $this->getSymb($inst->args[1]);
        $item2 = $this->getSymb($inst->args[2]);
        // TODO return code
        if ($item1->getType() != $item2->getType())
            return;

        $res = call_user_func_array(
            array($this, $cmpFun),
            array($item1->getValue(), $item2->getValue())
        );
        list($frame, $name) = explode('@', $inst->args[0]->getValue());
        $this->storage->add($frame, $name, "bool", $res);
    }

    private function and(Instruction $inst) {
        $item1 = $this->getSymb($inst->args[1]);
        $item2 = $this->getSymb($inst->args[2]);
        // TODO return code
        if ($item1->getType() != "bool" || $item2->getType() != "bool")
            return;

        $res = $item1->getValue() && $item2->getValue();
        list($frame, $name) = explode('@', $inst->args[0]->getValue());
        $this->storage->add($frame, $name, "bool", $res);
    }

    private function or(Instruction $inst) {
        $item1 = $this->getSymb($inst->args[1]);
        $item2 = $this->getSymb($inst->args[2]);
        // TODO return code
        if ($item1->getType() != "bool" || $item2->getType() != "bool")
            return;

        $res = $item1->getValue() || $item2->getValue();
        list($frame, $name) = explode('@', $inst->args[0]->getValue());
        $this->storage->add($frame, $name, "bool", $res);
    }

    private function not(Instruction $inst) {
        $item = $this->getSymb($inst->args[1]);
        if ($item->getType() != "bool")
            return;

        list($frame, $name) = explode('@', $inst->args[0]->getValue());
        $this->storage->add($frame, $name, "bool", !$item->getValue());
    }

    private function int2char(Instruction $inst) {
        $item = $this->getSymb($inst->args[1]);
        if ($item->getType() != "int")
            return;

        list($frame, $name) = explode('@', $inst->args[0]->getValue());
        $this->storage->add($frame, $name, "string", chr($item->getValue()));
    }

    private function stri2int(Instruction $inst) {
        $item1 = $this->getSymb($inst->args[1]);
        $item2 = $this->getSymb($inst->args[2]);
        if ($item1->getType() != "string" || $item2->getType() != "int")
            return;

        $res = ord($item1->getValue()[$item2->getValue()]);
        list($frame, $name) = explode('@', $inst->args[0]->getValue());
        $this->storage->add($frame, $name, "int", $res);
    }

    private function read(Instruction $inst) {
        $item = $this->getSymb($inst->args[1]);
        $value = match ($item->getValue()) {
            "int" => $this->input->readInt(),
            "string" => $this->input->readString(),
            "bool" => $this->input->readBool(),
        };

        $type = $value ? $item->getValue() : "nil";
        $res = $value ?? null;
        list($frame, $name) = explode('@', $inst->args[0]->getValue());
        $this->storage->add($frame, $name, $type, $res);
    }

    private function write(Instruction $inst) {
        $item = $this->getSymb($inst->args[0]);
        match ($item->getType()) {
            "int" => $this->stdout->writeInt((int)$item->getValue()),
            "string" => $this->stdout->writeString($item->getValue()),
            "bool" => $this->stdout->writeBool($item->getValue()),
            "nil" => $this->stdout->writeString(''),
        };
    }

    private function concat(Instruction $inst) {
        $item1 = $this->getSymb($inst->args[1]);
        $item2 = $this->getSymb($inst->args[2]);
        if ($item1->getType() != "string" || $item2->getType() != "string")
            return;

        $res = $item1->getValue() . $item2->getValue();
        list($frame, $name) = explode('@', $inst->args[0]);
        $this->storage->add($frame, $name, "string", $res);
    }

    private function strlen(Instruction $inst) {
        $item = $this->getSymb($inst->args[0]);
        if ($item->getType() != "string")
            return;

        $res = strlen($item->getValue());
        list($frame, $name) = explode('@', $inst->args[0]);
        $this->storage->add($frame, $name, "int", $res);
    }

    private function getchar(Instruction $inst) {
        $item1 = $this->getSymb($inst->args[1]);
        $item2 = $this->getSymb($inst->args[2]);

        if (!isset($item1->getValue()[$item2->getValue()]))
            return;

        $res = $item1->getValue()[$item2->getValue()];
        list($frame, $name) = explode('@', $inst->args[0]);
        $this->storage->add($frame, $name, "string", $res);
    }

    private function setchar(Instruction $inst) {
        $item1 = $this->getSymb($inst->args[1]);
        $item2 = $this->getSymb($inst->args[2]);
        if ($item1->getType() != "int" || $item2->getType() != "string")
            return;

        list($frame, $name) = explode('@', $inst->args[0]);
        $strArr = str_split($this->storage->get($frame, $name));
        $strArr[$item1->getValue()] = $item2->getValue()[0];
        $res = implode('', $strArr);
        $this->storage->add($frame, $name, "string", $res);
    }

    private function type(Instruction $inst) {
        $item = $this->getSymb($inst->args[0]);

        $res = $item->getType() ?? "string";
        list($frame, $name) = explode('@', $inst->args[0]);
        $this->storage->add($frame, $name, "string", $res);
    }

    private function label(Instruction $inst, int $pos) {
        $this->storage->addLabel($inst->args[0], $pos);
    }

    private function jump(Instruction $inst) {
        /// Need to set the position
        $pos = $this->storage->getLabel($inst->args[0]);
    }

    private function jumpifeq(Instruction $inst) {
        $item1 = $this->getSymb($inst->args[1]);
        $item2 = $this->getSymb($inst->args[2]);
        if ($item1->getType() != $item2->getType())
            return;

        if ($item1->getValue() != $item2->getValue())
            return;

        /// Need to set the position
        $pos = $this->storage->getLabel($inst->args[0]);
    }

    private function jumpifneq(Instruction $inst) {
        $item1 = $this->getSymb($inst->args[1]);
        $item2 = $this->getSymb($inst->args[2]);
        if ($item1->getType() != $item2->getType())
            return;

        if ($item1->getValue() == $item2->getValue())
            return;

        /// Need to set the position
        $pos = $this->storage->getLabel($inst->args[0]);
    }

    private function exit(Instruction $inst) {
        // TODO
    }

    private function dprint(Instruction $inst) {
        $item = $this->getSymb($inst->args[0]);
        match ($item->getType()) {
            "int" => $this->stderr->writeInt((int)$item->getValue()),
            "string" => $this->stderr->writeString($item->getValue()),
            "bool" => $this->stderr->writeBool($item->getValue()),
            "nil" => $this->stderr->writeString(''),
        };
    }

    private function breakInst() {
        // TODO
    }


    private function getSymb(Arg $arg): StorageItem {
        $type = $arg->getType();
        $value = $arg->getValue();
        $item = new StorageItem($type, $value);
        if ($type == "var") {
            list($fr, $n) = explode('@', $value);
            $item = $this->storage->get($fr, $n);
            $type = $item->getType();
            $value = $item->getValue();
        }

        return $item;
    }

    private function save(string $var, ?string $type, mixed $value) {
        list($frame, $name) = explode('@', $var);
        $this->storage->add($frame, $name, $type, $value);
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
