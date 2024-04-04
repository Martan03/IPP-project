<?php
/**
 * IPP - XML parser
 * @author Martin Slezák - xsleza26
 */

namespace IPP\Student;

use DOMDocument;
use DOMElement;
use IPP\Core\Exception\XMLException;

/**
 * Class that parses XML
 */
class XMLParser {
    /** @var array<int, Instruction> array containing instructions */
    public array $instructions;

    public function __construct() {
        $this->instructions = [];
    }

    /**
     * Parses given dom document
     * @param DOMDocument $dom dom document containing XML
     */
    public function parse(DOMDocument $dom): void {
        if ($dom->childElementCount > 1)
            throw new XMLException("Invalid XML format");

        $program = $dom->firstElementChild;
        if (!$program || $program->nodeName !== "program")
            throw new XMLException("Expected program tag");

        if ($program->getAttribute("language") !== "IPPcode24")
            throw new XMLException("Invalid program language");

        foreach ($program->childNodes as $child) {
            if (!$child instanceof DOMElement)
                continue;

            $this->parse_inst($child);
        }
    }

    /**
     * Returns instructions array
     * @return array<int, Instruction> array containing instructions
     */
    public function get_instructions(): array {
        return $this->instructions;
    }

    /**
     * Parses instruction
     * @param DOMElement $element element containing instruction
     */
    private function parse_inst(DOMElement $element): void {
        if ($element->nodeName !== "instruction")
            throw new XMLException("Expected instruction");
        if (!$element->hasAttribute("opcode"))
            throw new XMLException("Instruction has to contain opcode");
        if (!$element->hasAttribute("order"))
            throw new XMLException("Instruction has to contain order");
        if (!is_numeric($element->getAttribute("order")))
            throw new XMLException("Instruction order has to be a number");
        if ($element->attributes->length > 2)
            throw new XMLException("Unexpected arguments in instruction");

        $inst = new Instruction($element->getAttribute("opcode"));
        foreach ($element->childNodes as $arg) {
            if (!$arg instanceof DOMElement)
                continue;

            $inst->addArg($this->parse_arg($arg));
        }

        $order = (int)$element->getAttribute("order");
        if ($order <= 0 || isset($this->instructions[$order]))
            throw new XMLException("Invalid or existing order");

        $this->instructions[$order] = $inst;
    }

    /**
     * Parses argument
     * @param DOMElement $arg argument to be parsed
     * @return Arg parsed argument
     */
    private function parse_arg(DOMElement $arg): Arg {
        if (!$arg->hasAttribute("type"))
            throw new XMLException("Argument has to contain type");
        if ($arg->attributes->length > 1)
            throw new XMLException("Unexpected arguments in instruction");

        return new Arg(
            $arg->getAttribute("type"),
            trim($arg->nodeValue ?? "")
        );
    }
}