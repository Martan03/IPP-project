<?php

namespace IPP\Student;

use IPP\Core\AbstractInterpreter;
use IPP\Student\Exception\ExitException;
use IPP\Student\Exception\OperandTypeException;
use IPP\Student\Exception\OperandValueException;
use IPP\Student\Exception\SemanticException;
use IPP\Student\Exception\StringOperationException;
use IPP\Student\Exception\ValueException;

class Interpreter extends AbstractInterpreter
{
    /**
     * @var array<int, Instruction> instructions array
     */
    private array $instructions;
    private Storage $storage;
    private ?int $pos;
    private int $exec = 0;

    public function execute(): int {
        $parser = new XMLParser();
        $parser->parse($this->source->getDOMDocument());
        $this->instructions = $parser->get_instructions();
        ksort($this->instructions);

        $this->storage = new Storage();
        $this->parseLabels();

        reset($this->instructions);
        try {
            while (($this->pos = key($this->instructions)) !== null) {
                $this->execInstruction($this->instructions[$this->pos]);
                next($this->instructions);
            }
        } catch (ExitException $e) {
            return $e->ret_value;
        }

        /*
        echo "\nMemory:\n";
        echo var_dump($this->storage);
        */
        return 0;
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
        match (strtoupper($inst->opcode)) {
            "MOVE" => $this->move($inst),
            "CREATEFRAME" => $this->storage->createFrame(),
            "PUSHFRAME" => $this->storage->pushFrame(),
            "POPFRAME" => $this->storage->popFrame(),
            "DEFVAR" => $this->defVar($inst),
            "CALL" => $this->call($inst),
            "RETURN" => $this->ret(),
            "PUSHS" => $this->pushs($inst),
            "POPS" => $this->pops($inst),
            "ADD" => $this->calc($inst, [$this, "sum"]),
            "SUB" => $this->calc($inst, [$this, "sub"]),
            "MUL" => $this->calc($inst, [$this, "mul"]),
            "IDIV" => $this->calc($inst, [$this, "div"]),
            "LT" => $this->cmp($inst, [$this, "lt"]),
            "GT" => $this->cmp($inst, [$this, "gt"]),
            "EQ" => $this->cmp($inst, [$this, "eq"]),
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
        $this->exec++;
    }

    /**
     * Moves value given by second operand to memory given by first operand
     * @param Instruction $inst instruction containing operands
     */
    private function move(Instruction $inst): void {
        $item = $this->getSymb($inst->args[1]);

        list($frame, $name) = explode('@', $inst->args[0]->getValue());
        $this->storage
            ->add($frame, $name, $item->getType(), $item->getValue());
    }

    /**
     * Defines variable on frame with name given by first operand
     * @param Instruction $inst instruction containing operands
     */
    private function defVar(Instruction $inst): void {
        list($frame, $name) = explode('@', $inst->args[0]->getValue());
        $this->storage->defVar($frame, $name);
    }

    /**
     * Saves next instruction position to call stack and jumps to label given
     * by first operand
     * @param Instruction $inst instruction containing operands
     */
    private function call(Instruction $inst): void {
        next($this->instructions);
        $pos = key($this->instructions);
        $this->storage->pushCall($pos);

        $this->jump($inst);
    }

    /**
     * Jumps to position from call stack
     */
    private function ret(): void {
        $pos = $this->storage->popCall();
        reset($this->instructions);
        while (key($this->instructions) !== $pos &&
               next($this->instructions) !== false);
    }

    /**
     * Pushes value given by first operand to value stack
     * @param Instruction $inst instruction containing operands
     */
    private function pushs(Instruction $inst): void {
        $item = $this->getSymb($inst->args[0]);
        $this->storage->push($item);
    }

    /**
     * Pops item from value stack and assign it to variable given by first
     * operand
     * @param Instruction $inst instruction containing operands
     */
    private function pops(Instruction $inst): void {
        list($frame, $name) = explode('@', $inst->args[0]->getValue());
        $this->storage->pop($frame, $name);
    }

    /**
     * Calculates using given function with second and third operand value.
     * Result is saved to variable given by first operand
     * @param Instruction $inst instruction containing operands
     * @param callable $calculate function to calculate expression
     */
    private function calc(Instruction $inst, callable $calculate): void {
        $item1 = $this->getSymb($inst->args[1]);
        $item2 = $this->getSymb($inst->args[2]);
        if ($item1->getType() != 'int' || $item2->getType() != 'int')
            throw new OperandTypeException("Can calculate only with integer");

        $res = $calculate((int)$item1->getValue(), (int)$item2->getValue());
        list($frame, $name) = explode('@', $inst->args[0]->getValue());
        $this->storage->add($frame, $name, "int", $res);
    }

    /**
     * Compares second and third operand value using given function.
     * Result is saved to variable given by first operand
     * @param Instruction $inst instruction containing operands
     * @param callable $cmpFun comparison function
     */
    private function cmp(Instruction $inst, callable $cmpFun): void {
        $item1 = $this->getSymb($inst->args[1]);
        $item2 = $this->getSymb($inst->args[2]);
        if ($item1->getType() != $item2->getType())
            throw new OperandTypeException("Can compare same types only");

        $res = $cmpFun($item1->getValue(), $item2->getValue());
        list($frame, $name) = explode('@', $inst->args[0]->getValue());
        $this->storage->add($frame, $name, "bool", $res);
    }

    /**
     * Applies logical and on second and third operand.
     * Result is saved to variable given by first operand
     * @param Instruction $inst instruction containing operands
     */
    private function and(Instruction $inst): void {
        $item1 = $this->getSymb($inst->args[1]);
        $item2 = $this->getSymb($inst->args[2]);
        if ($item1->getType() != "bool" || $item2->getType() != "bool")
            throw new OperandTypeException(
                "boolean operators can be applied to bool only
            ");

        $res = $item1->getValue() && $item2->getValue();
        list($frame, $name) = explode('@', $inst->args[0]->getValue());
        $this->storage->add($frame, $name, "bool", $res);
    }

    /**
     * Logical or on second and third operand.
     * Result is saved in variable given by first operand
     * @param Instruction $inst instruction containing operands
     */
    private function or(Instruction $inst): void {
        $item1 = $this->getSymb($inst->args[1]);
        $item2 = $this->getSymb($inst->args[2]);
        if ($item1->getType() != "bool" || $item2->getType() != "bool")
            throw new OperandTypeException(
                "boolean operators can be applied to bool only
            ");

        $res = $item1->getValue() || $item2->getValue();
        list($frame, $name) = explode('@', $inst->args[0]->getValue());
        $this->storage->add($frame, $name, "bool", $res);
    }

    /**
     * Applies negation to the symbol given by second operand.
     * Result is saved to variable given by first operand
     * @param Instruction $inst instruction containing operands
     */
    private function not(Instruction $inst): void {
        $item = $this->getSymb($inst->args[1]);
        if ($item->getType() != "bool")
            throw new OperandTypeException(
                "Boolean operators can be applied to bool only
            ");

        list($frame, $name) = explode('@', $inst->args[0]->getValue());
        $this->storage->add($frame, $name, "bool", !$item->getValue());
    }

    /**
     * Converts ordinal value given by second operand to char.
     * Result is saved to variable given by first operand
     * @param Instruction $inst instruction containing operands
     */
    private function int2char(Instruction $inst): void {
        $item = $this->getSymb($inst->args[1]);
        if ($item->getType() != "int")
            return;

        $char = mb_chr($item->getValue(), "UTF-8");
        if ($char === false)
            throw new StringOperationException("invalid ordinal value");

        list($frame, $name) = explode('@', $inst->args[0]->getValue());
        $this->storage->add($frame, $name, "string", $char);
    }

    /**
     * Get ordinal value of character in string given by second operand on
     * position given by third operand. Result is saved to variable given by
     * first operand
     * @param Instruction $inst instruction containing operands
     */
    private function stri2int(Instruction $inst): void {
        $item1 = $this->getSymb($inst->args[1]);
        $item2 = $this->getSymb($inst->args[2]);
        if ($item1->getType() != "string" || $item2->getType() != "int")
            throw new OperandTypeException();

        $res = mb_ord($item1->getValue()[$item2->getValue()]);
        if ($res === false)
            throw new StringOperationException();

        list($frame, $name) = explode('@', $inst->args[0]->getValue());
        $this->storage->add($frame, $name, "int", $res);
    }

    /**
     * Reads type given by second operand to variable given by first operand
     * @param Instruction $inst instruction containing operands
     */
    private function read(Instruction $inst): void {
        $item = $this->getSymb($inst->args[1]);
        if ($item->getType() != "type")
            throw new OperandTypeException();

        $value = match ($item->getValue()) {
            "int" => $this->input->readInt(),
            "string" => $this->input->readString(),
            "bool" => $this->input->readBool(),
            default => throw new OperandValueException("Invalid type given"),
        };

        $type = $value ? $item->getValue() : "nil";
        list($frame, $name) = explode('@', $inst->args[0]->getValue());
        $this->storage->add($frame, $name, $type, $value);
    }

    /**
     * Writes symbol given by first operand to stdout
     * @param Instruction $inst instruction containing operands
     */
    private function write(Instruction $inst): void {
        $item = $this->getSymb($inst->args[0]);
        match ($item->getType()) {
            "int" => $this->stdout->writeInt((int)$item->getValue()),
            "string" => $this->stdout->writeString(
                $this->replaceString($item->getValue())),
            "bool" => $this->stdout->writeBool($item->getValue()),
            "nil" => $this->stdout->writeString(''),
            default => throw new ValueException(
                "tried to print unsupported type"
            ),
        };
    }

    /**
     * Concats two strings given by second and third operands and saves it to
     * variable given by first operand
     * @param Instruction $inst instruction containing operands
     */
    private function concat(Instruction $inst): void {
        $item1 = $this->getSymb($inst->args[1]);
        $item2 = $this->getSymb($inst->args[2]);
        if ($item1->getType() != "string" || $item2->getType() != "string")
            throw new OperandTypeException("can concat strings only");

        $res = $item1->getValue() . $item2->getValue();
        list($frame, $name) = explode('@', $inst->args[0]->getValue());
        $this->storage->add($frame, $name, "string", $res);
    }

    /**
     * Gets string length of string given by second operand and saves it to
     * variable given by first operand
     * @param Instruction $inst instruction containing operands
     */
    private function strlen(Instruction $inst): void {
        $item = $this->getSymb($inst->args[1]);
        if ($item->getType() != "string")
            throw new OperandTypeException("strlen expects string");

        $res = strlen($item->getValue());
        list($frame, $name) = explode('@', $inst->args[0]->getValue());
        $this->storage->add($frame, $name, "int", $res);
    }

    /**
     * Gets char from string given by second operand on position given by third
     * operand and saves it to variable given by first operand
     * @param Instruction $inst instruction containing operands
     */
    private function getchar(Instruction $inst): void {
        $item1 = $this->getSymb($inst->args[1]);
        $item2 = $this->getSymb($inst->args[2]);
        if ($item1->getType() != "string" || $item2->getType() != "int")
            throw new OperandTypeException("GETCHAR expects string and int");

        if (!isset($item1->getValue()[(int)$item2->getValue()]))
            throw new StringOperationException("GETCHAR index out of range");

        $res = $item1->getValue()[$item2->getValue()];
        list($frame, $name) = explode('@', $inst->args[0]->getValue());
        $this->storage->add($frame, $name, "string", $res);
    }

    /**
     * Modifies char in string saved in variable given by first operand on
     * position given by second operand to char given by third operand.
     * @param Instruction $inst instruction containing operands
     */
    private function setchar(Instruction $inst): void {
        $item1 = $this->getSymb($inst->args[1]);
        $item2 = $this->getSymb($inst->args[2]);
        if ($item1->getType() != "int" || $item2->getType() != "string")
            throw new OperandTypeException("SETCHAR expects int and string");

        list($frame, $name) = explode('@', $inst->args[0]->getValue());
        $strArr = str_split($this->storage->get($frame, $name)->getValue());

        if (!isset($item2->getValue()[0]) ||
            !isset($strArr[(int)$item1->getValue()]))
            throw new StringOperationException("SETCHAR index out of range");

        $strArr[$item1->getValue()] = $item2->getValue()[0];
        $res = implode('', $strArr);
        $this->storage->add($frame, $name, "string", $res);
    }

    /**
     * Gets type of the symbol given by second operand and saves it to first
     * operand
     * @param Instruction $inst instruction containing operands
     */
    private function type(Instruction $inst): void {
        $item = $this->getSymb($inst->args[1]);

        $res = $item->getType() ?? "";
        list($frame, $name) = explode('@', $inst->args[0]->getValue());
        $this->storage->add($frame, $name, "string", $res);
    }

    /**
     * Jumps to the label given by first operand
     * @param Instruction $inst instruction containing operands
     */
    private function jump(Instruction $inst): void {
        $label = $inst->args[0]->getValue();
        if (!$this->storage->labelExists($label))
            throw new SemanticException(
                "Label '" . $label . "' doesn't exist");

        $pos = $this->storage->getLabel($label);
        reset($this->instructions);
        while (key($this->instructions) !== $pos &&
               next($this->instructions) !== false);
    }

    /**
     * Jumps to the label given by first operand if values of second and third
     * operand are equal
     * @param Instruction $inst instruction containing operands
     */
    private function jumpifeq(Instruction $inst): void {
        $item1 = $this->getSymb($inst->args[1]);
        $item2 = $this->getSymb($inst->args[2]);
        if ($item1->getType() != $item2->getType())
            throw new OperandTypeException();

        if ($item1->getValue() != $item2->getValue())
            return;

        $label = $inst->args[0]->getValue();
        if (!$this->storage->labelExists($label))
            throw new SemanticException(
                "Label '" . $label . "' doesn't exist");

        $pos = $this->storage->getLabel($label);
        reset($this->instructions);
        while (key($this->instructions) !== $pos &&
               next($this->instructions) !== false);
    }

    /**
     * Jumps to the label given by first operand if values of second and third
     * operand aren't equal
     * @param Instruction $inst instruction containing operands
     */
    private function jumpifneq(Instruction $inst): void {
        $item1 = $this->getSymb($inst->args[1]);
        $item2 = $this->getSymb($inst->args[2]);
        if ($item1->getType() != $item2->getType())
            throw new OperandTypeException();

        if ($item1->getValue() == $item2->getValue())
            return;

        $label = $inst->args[0]->getValue();
        if (!$this->storage->labelExists($label))
            throw new SemanticException(
                "Label '" . $label . "' doesn't exist");

        $pos = $this->storage->getLabel($label);
        reset($this->instructions);
        while (key($this->instructions) !== $pos &&
               next($this->instructions) !== false);
    }

    /**
     * Exists program with exit code given by first operand
     * @param Instruction $inst instruction containing operands
     */
    private function exit(Instruction $inst): void {
        $item = $this->getSymb($inst->args[0]);
        if ($item->getType() != "int")
            throw new OperandTypeException();

        if ($item->getValue() < 0 || $item->getValue() > 9)
            throw new OperandValueException("return type not in valid range");

        throw new ExitException($item->getValue());
    }

    /**
     * Prints symbol given by first operand to stderr
     * @param Instruction $inst instruction containing operands
     */
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

    /**
     * Prints interpret state to stderr
     * @param Instruction $inst instruction containing operands
     */
    private function breakInst(): void {
        $this->stderr->writeString("Executed instructions: " . $this->exec);
        $this->stderr->writeString("\nCurrent instruction: " . $this->pos);
    }

    /**
     * Gets symbol based on the given argument
     * @param Arg $arg argument containg symbol
     * @return StorageItem retrieved symbol
     */
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

    /**
     * Saves variable to the storage
     * @param string $var variable string containg frame and variable name
     * @param ?string $type type of the value to be saved
     * @param mixed $value value to be saved
     */
    private function save(string $var, ?string $type, mixed $value): void {
        list($frame, $name) = explode('@', $var);
        $this->storage->add($frame, $name, $type, $value);
    }

    /**
     * Checks if first value is less then second
     * @param mixed $val1 first value
     * @param mixed $val2 second value
     * @return bool true if less, else false
     */
    private function lt(mixed $val1, mixed $val2): bool {
        if (!isset($val1) || !isset($val2))
            throw new OperandTypeException('nill cannot be used in `lt`');
        return $val1 < $val2;
    }

    /**
     * Checks if first value is greater then second
     * @param mixed $val1 first value
     * @param mixed $val2 second value
     * @return bool true if greater, else false
     */
    private function gt(mixed $val1, mixed $val2): bool {
        if (!isset($val1) || !isset($val2))
            throw new OperandTypeException('nill cannot be used in `gt`');
        return $val1 > $val2;
    }

    /**
     * Checks if first value is equal to second
     * @param mixed $val1 first value
     * @param mixed $val2 second value
     * @return bool true if equal, else false
     */
    private function eq(mixed $val1, mixed $val2): bool {
        return $val1 == $val2;
    }

    /**
     * Sums two values
     * @param mixed $val1 first value
     * @param mixed $val2 second value
     * @return int sum result
     */
    private function sum(int $val1, int $val2): int {
        return $val1 + $val2;
    }

    /**
     * Subtracts two values
     * @param mixed $val1 first value
     * @param mixed $val2 second value
     * @return int subtraction result
     */
    private function sub(int $val1, int $val2): int {
        return $val1 - $val2;
    }

    /**
     * Multiplies two values
     * @param mixed $val1 first value
     * @param mixed $val2 second value
     * @return int multiplication result
     */
    private function mul(int $val1, int $val2): int {
        return $val1 * $val2;
    }

    /**
     * Divides two values
     * @param mixed $val1 first value
     * @param mixed $val2 second value
     * @return int division result
     */
    private function div(int $val1, int $val2): int {
        if ($val2 === 0)
            throw new OperandValueException("Cannot divide by 0");

        return $val1 / $val2;
    }

    /**
     * Replaces escape sequence in the string
     * @param string $text string to replace sequences in
     * @return string string with replaced sequences
     */
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
