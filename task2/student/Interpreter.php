<?php

namespace IPP\Student;

use DOMText;
use IPP\Core\AbstractInterpreter;
use IPP\Core\Exception\XMLException;
use IPP\Student\Exception\OperandTypeException;
use IPP\Student\Exception\OperandValueException;
use IPP\Student\Exception\SemanticException;
use IPP\Student\Exception\StringOperationException;

class Interpreter extends AbstractInterpreter
{
    /**
     * @var array<int, Instruction> instructions array
     */
    private array $instructions;
    private Storage $storage;
    private ?int $pos;

    public function execute(): int
    {
        $this->instructions = $this->parseXml();
        $this->storage = new Storage();

        ksort($this->instructions);

        $this->parseLabels();

        reset($this->instructions);
        while (($this->pos = key($this->instructions)) !== null) {
            $this->execInstruction($this->instructions[$this->pos]);
            next($this->instructions);
        }

        /*
        echo "\nMemory:\n";
        echo var_dump($this->storage);
        */
        return 0;
    }

    /**
     * Parses input XML and gets array of Instructions
     * @return array<int, Instruction> array containing instructions
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
                    trim($arg->nodeValue ?? ""),
                ));
            }

            $order = (int)$opcode->getAttribute("order") - 1;
            if ($order < 0 || isset($instructions[$order]))
                throw new XMLException();

            $instructions[$order] = $inst;
        }

        return $instructions;
    }

    private function parseLabels(): void {
        foreach ($this->instructions as $key => $inst) {
            if ($inst->opcode != "LABEL")
                continue;

            $label = $inst->args[0]->getValue();
            if (!$this->storage->addLabel($label, $key)) {
                throw new SemanticException(
                    "Label '" . $label . "' is defined more than once"
                );
            }
        }
    }

    private function execInstruction(Instruction $inst): void {
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
            "ADD" => $this->calc($inst, function (int $x, int $y) {
                return $this->sum($x, $y);
            }),
            "SUB" => $this->calc($inst, function (int $x, int $y) {
                return $this->sub($x, $y);
            }),
            "MUL" => $this->calc($inst, function (int $x, int $y) {
                return $this->mul($x, $y);
            }),
            "IDIV" => $this->calc($inst, function (int $x, int $y) {
                return $this->div($x, $y);
            }),
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
            "JUMP" => $this->jump($inst),
            "JUMPIFEQ" => $this->jumpifeq($inst),
            "JUMPIFNEQ" => $this->jumpifneq($inst),
            "EXIT" => $this->exit($inst),
            "DPRINT" => $this->dprint($inst),
            "BREAK" => $this->breakInst(),
            default => function() {},
        };
    }

    private function none(): void {}

    private function move(Instruction $inst): void {
        $item = $this->getSymb($inst->args[1]);

        list($frame, $name) = explode('@', $inst->args[0]->getValue());
        $this->storage
            ->add($frame, $name, $item->getType(), $item->getValue());
    }

    private function defVar(Instruction $inst): void {
        list($frame, $name) = explode('@', $inst->args[0]->getValue());
        if (!$this->storage->defVar($frame, $name))
            throw new SemanticException();
    }

    private function calc(Instruction $inst, callable $calculate): void {
        $item1 = $this->getSymb($inst->args[1]);
        $item2 = $this->getSymb($inst->args[2]);
        if ($item1->getType() != 'int' || $item2->getType() != 'int')
            throw new OperandTypeException("Can calculate only with integer");

        $res = $calculate((int)$item1->getValue(), (int)$item2->getValue());
        list($frame, $name) = explode('@', $inst->args[0]->getValue());
        $this->storage->add($frame, $name, "int", $res);
    }

    private function cmp(Instruction $inst, string $cmpFun): void {
        $item1 = $this->getSymb($inst->args[1]);
        $item2 = $this->getSymb($inst->args[2]);
        if ($item1->getType() != $item2->getType())
            throw new OperandTypeException("Can compare same types only");

        $res = call_user_func_array(
            array($this, $cmpFun),
            array($item1->getValue(), $item2->getValue())
        );
        list($frame, $name) = explode('@', $inst->args[0]->getValue());
        $this->storage->add($frame, $name, "bool", $res);
    }

    private function and(Instruction $inst): void {
        $item1 = $this->getSymb($inst->args[1]);
        $item2 = $this->getSymb($inst->args[2]);
        if ($item1->getType() != "bool" || $item2->getType() != "bool")
            throw new OperandTypeException(
                "Boolean operators can be applied to bool only
            ");

        $res = $item1->getValue() && $item2->getValue();
        list($frame, $name) = explode('@', $inst->args[0]->getValue());
        $this->storage->add($frame, $name, "bool", $res);
    }

    private function or(Instruction $inst): void {
        $item1 = $this->getSymb($inst->args[1]);
        $item2 = $this->getSymb($inst->args[2]);
        if ($item1->getType() != "bool" || $item2->getType() != "bool")
            throw new OperandTypeException(
                "Boolean operators can be applied to bool only
            ");

        $res = $item1->getValue() || $item2->getValue();
        list($frame, $name) = explode('@', $inst->args[0]->getValue());
        $this->storage->add($frame, $name, "bool", $res);
    }

    private function not(Instruction $inst): void {
        $item = $this->getSymb($inst->args[1]);
        if ($item->getType() != "bool")
            throw new OperandTypeException(
                "Boolean operators can be applied to bool only
            ");

        list($frame, $name) = explode('@', $inst->args[0]->getValue());
        $this->storage->add($frame, $name, "bool", !$item->getValue());
    }

    private function int2char(Instruction $inst): void {
        $item = $this->getSymb($inst->args[1]);
        if ($item->getType() != "int")
            return;

        list($frame, $name) = explode('@', $inst->args[0]->getValue());
        $this->storage->add($frame, $name, "string", chr($item->getValue()));
    }

    private function stri2int(Instruction $inst): void {
        $item1 = $this->getSymb($inst->args[1]);
        $item2 = $this->getSymb($inst->args[2]);
        if ($item1->getType() != "string" || $item2->getType() != "int")
            return;

        $res = ord($item1->getValue()[$item2->getValue()]);
        list($frame, $name) = explode('@', $inst->args[0]->getValue());
        $this->storage->add($frame, $name, "int", $res);
    }

    private function read(Instruction $inst): void {
        $item = $this->getSymb($inst->args[1]);
        $value = match ($item->getValue()) {
            "int" => $this->input->readInt(),
            "string" => $this->input->readString(),
            "bool" => $this->input->readBool(),
            default => throw new OperandValueException("Invalid type given"),
        };

        $type = $value ? $item->getValue() : "nil";
        $res = $value ?? null;
        list($frame, $name) = explode('@', $inst->args[0]->getValue());
        $this->storage->add($frame, $name, $type, $res);
    }

    private function write(Instruction $inst): void {
        $item = $this->getSymb($inst->args[0]);
        match ($item->getType()) {
            "int" => $this->stdout->writeInt((int)$item->getValue()),
            "string" => $this->stdout->writeString(
                $this->replaceString($item->getValue())),
            "bool" => $this->stdout->writeBool($item->getValue()),
            "nil" => $this->stdout->writeString(''),
            default => throw new OperandTypeException(
                "tried to print invalid type"
            ),
        };
    }

    private function concat(Instruction $inst): void {
        $item1 = $this->getSymb($inst->args[1]);
        $item2 = $this->getSymb($inst->args[2]);
        if ($item1->getType() != "string" || $item2->getType() != "string")
            return;

        $res = $item1->getValue() . $item2->getValue();
        list($frame, $name) = explode('@', $inst->args[0]->getValue());
        $this->storage->add($frame, $name, "string", $res);
    }

    private function strlen(Instruction $inst): void {
        $item = $this->getSymb($inst->args[0]);
        if ($item->getType() != "string")
            return;

        $res = strlen($item->getValue());
        list($frame, $name) = explode('@', $inst->args[0]->getValue());
        $this->storage->add($frame, $name, "int", $res);
    }

    private function getchar(Instruction $inst): void {
        $item1 = $this->getSymb($inst->args[1]);
        $item2 = $this->getSymb($inst->args[2]);
        if ($item1->getType() != "string" || $item2->getType() != "int")
            throw new OperandTypeException("GETCHAR excepts string and int");

        if (!isset($item1->getValue()[(int)$item2->getValue()]))
            throw new StringOperationException("GETCHAR index out of range");

        $res = $item1->getValue()[$item2->getValue()];
        list($frame, $name) = explode('@', $inst->args[0]->getValue());
        $this->storage->add($frame, $name, "string", $res);
    }

    private function setchar(Instruction $inst): void {
        $item1 = $this->getSymb($inst->args[1]);
        $item2 = $this->getSymb($inst->args[2]);
        if ($item1->getType() != "int" || $item2->getType() != "string")
            throw new OperandTypeException("SETCHAR excepts int and string");

        list($frame, $name) = explode('@', $inst->args[0]->getValue());
        $strArr = str_split($this->storage->get($frame, $name)->getValue());

        if (!isset($item2->getValue()[0]) ||
            !isset($strArr[(int)$item1->getValue()]))
            throw new StringOperationException("SETCHAR index out of range");

        $strArr[$item1->getValue()] = $item2->getValue()[0];
        $res = implode('', $strArr);
        $this->storage->add($frame, $name, "string", $res);
    }

    private function type(Instruction $inst): void {
        $item = $this->getSymb($inst->args[0]);

        $res = $item->getType() ?? "string";
        list($frame, $name) = explode('@', $inst->args[0]->getValue());
        $this->storage->add($frame, $name, "string", $res);
    }

    private function jump(Instruction $inst): void {
        $label = $inst->args[0]->getValue();
        $pos = $this->storage->getLabel($label);
        if (!$pos) {
            throw new SemanticException(
                "Label '" . $label . "' doesn't exist");
        }

        reset($this->instructions);
        while (key($this->instructions) !== $pos &&
               next($this->instructions) !== false);
    }

    private function jumpifeq(Instruction $inst): void {
        $item1 = $this->getSymb($inst->args[1]);
        $item2 = $this->getSymb($inst->args[2]);
        if ($item1->getType() != $item2->getType())
            return;

        if ($item1->getValue() != $item2->getValue())
            return;

        $pos = $this->storage->getLabel($inst->args[0]->getValue());
        reset($this->instructions);
        while (key($this->instructions) !== $pos &&
               next($this->instructions) !== false);
    }

    private function jumpifneq(Instruction $inst): void {
        $item1 = $this->getSymb($inst->args[1]);
        $item2 = $this->getSymb($inst->args[2]);
        if ($item1->getType() != $item2->getType())
            return;

        if ($item1->getValue() == $item2->getValue())
            return;

        $pos = $this->storage->getLabel($inst->args[0]->getValue());
        reset($this->instructions);
        while (key($this->instructions) !== $pos &&
               next($this->instructions) !== false);
    }

    private function exit(Instruction $inst): void {
        // TODO
    }

    private function dprint(Instruction $inst): void {
        $item = $this->getSymb($inst->args[0]);
        match ($item->getType()) {
            "int" => $this->stderr->writeInt((int)$item->getValue()),
            "string" => $this->stderr->writeString($item->getValue()),
            "bool" => $this->stderr->writeBool($item->getValue()),
            "nil" => $this->stderr->writeString(''),
            default => throw new OperandTypeException(
                "Cannot print this type"
            ),
        };
    }

    private function breakInst(): void {
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

    private function save(string $var, ?string $type, mixed $value): void {
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

    private function sum(int $val1, int $val2): int {
        return $val1 + $val2;
    }

    private function sub(int $val1, int $val2): int {
        return $val1 - $val2;
    }

    private function mul(int $val1, int $val2): int {
        return $val1 * $val2;
    }

    private function div(int $val1, int $val2): int {
        if ($val2 === 0)
            throw new OperandValueException("Cannot divide by 0");

        return $val1 / $val2;
    }

    private function replaceString(string $text): string {
        return preg_replace_callback(
            '/\\\\([0-9]{3})/',
            function ($matches) {
                return chr((int)$matches[1]);
            },
            $text
        );
    }
}
