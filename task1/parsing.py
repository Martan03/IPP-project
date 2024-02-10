import sys
from xml.dom.minidom import parseString
import xml.etree.ElementTree as ET

from lexer import Lexer
from token import TokenType

# Array containing all instructions
INSTRUCTIONS = {
    "move": 2, "createframe": 0, "pushframe": 0, "popframe": 0, "defvar": 1,
    "call": 1, "return": 0, "pushs": 1, "pops": 1, "add": 3, "sub": 3,
    "mul": 3, "idiv": 3, "lt": 3, "gt": 3, "eq": 3, "and": 3, "or": 3,
    "not": 2, "int2char": 2, "stri2int": 3, "read": 2, "write": 1, "concat": 3,
    "strlen": 2, "getchar": 3, "setchar": 3, "type": 2, "label": 1, "jump": 1,
    "jumpifeq": 3, "jumpifneq": 3, "exit": 1, "dprint": 1, "break": 0
}

class Parser:
    # Constructs new parser
    def __init__(self, text):
        self.lexer = Lexer(text)
        self.token = self.lexer.next()
        self.xml = ET.Element("program")
        self.xml.set("language", "IPPcode24")
        self.order = 1

    # Parses given text
    def parse(self):
        # Checks if code contains header
        if (self.token.type != TokenType.LABEL and
            self.token.value != "IPPcode24"):
            sys.exit(21)

        # Checks for new line after header
        self.token = self.lexer.next()
        if self.token.type != TokenType.EOL:
            sys.exit(23)

        # Parses tokens until end of file
        self.token = self.lexer.next()
        while self.token.type != TokenType.EOF:
            self.parse_line()
            self.token = self.lexer.next()

        dom = parseString(ET.tostring(self.xml))
        xml_bytes = dom.toprettyxml(indent="    ", encoding="utf-8")
        pretty_xml = xml_bytes.decode('utf-8')
        return pretty_xml

    # Parses line of code
    def parse_line(self):
        # Skips new line
        if self.token.type == TokenType.EOL:
            return

        # Line has to start with valid instruction
        if (self.token.type != TokenType.LABEL or
            self.token.value.lower() not in INSTRUCTIONS):
            sys.exit(22)

        opcode_el = ET.SubElement(self.xml, "instruction")
        opcode_el.set("order", str(self.order))
        opcode_el.set("opcode", self.token.value.upper())

        # Checks correct number of arguments
        exp_arg_cnt = INSTRUCTIONS[self.token.value.lower()]
        arg_cnt = 0
        self.token = self.lexer.next()
        while (self.token.type != TokenType.EOL and
               self.token.type != TokenType.EOF):
            arg_cnt += 1
            arg_el = ET.SubElement(opcode_el, f"arg{arg_cnt}")
            arg_el.set("type", self.token.type.value)
            arg_el.text = self.escape(self.token.value)
            self.token = self.lexer.next()

        if arg_cnt > exp_arg_cnt:
            sys.exit(23)

        self.order += 1

    def escape(self, text):
        esc = {
            "<": "&lt;",
            ">": "&gt;",
            "&": "&amp;",
            '"': "&quot;",
            "'": "&apos;",
        }
        return "".join(esc.get(c, c) for c in text)
