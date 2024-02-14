<?php

namespace IPP\Student;

use DOMText;
use IPP\Core\AbstractInterpreter;
use IPP\Core\Exception\NotImplementedException;
use IPP\Core\Exception\XMLException;
use IPP\Core\Settings;

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
        foreach ($this->instructions as $inst) {
            $inst->execute($this->storage);
        }

        echo var_dump($this->storage);

        throw new NotImplementedException;
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
}
