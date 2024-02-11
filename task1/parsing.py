import sys
from xml.dom.minidom import parseString
import xml.etree.ElementTree as ET

from lexer import Lexer
from token import TokenType

from constants import INSTRUCTIONS

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
        self._parse_header()

        # Parses tokens until end of file
        self.token = self.lexer.next()
        while self.token.type != TokenType.EOF:
            self._parse_line()
            self.token = self.lexer.next()

        # Creates XML code
        dom = parseString(ET.tostring(self.xml))
        xml_bytes = dom.toprettyxml(indent="    ", encoding="UTF-8")
        pretty_xml = xml_bytes.decode('UTF-8')
        return pretty_xml

    # Parses code header
    def _parse_header(self):
        # Skips new lines
        while self.token.type == TokenType.EOL:
            self.token = self.lexer.next()

        # Checks if code contains header
        if (self.token.type != TokenType.NALABEL or
            self.token.value.lower() != ".ippcode24"):
            sys.exit(21)

        # Checks for new line after header or end of file
        self.token = self.lexer.next()
        if (self.token.type != TokenType.EOL and
            self.token.type != TokenType.EOF):
            sys.exit(21)

    # Parses line of code
    def _parse_line(self):
        # Skips new line
        if self.token.type == TokenType.EOL:
            return

        # Line has to start with valid instruction
        opcode = self.token.value.upper()
        if (self.token.type != TokenType.LABEL or
            opcode not in INSTRUCTIONS):
            print(self.token.type)
            print(self.token.value)
            sys.exit(22)

        # Creates instruction element in XML
        opcode_el = ET.SubElement(self.xml, "instruction")
        opcode_el.set("order", str(self.order))
        opcode_el.set("opcode", opcode)

        # Checks argument types
        exp_args = INSTRUCTIONS[opcode]
        self.token = self.lexer.next()
        for i, arg_type in enumerate(exp_args):
            if not self._is_arg_valid(arg_type):
                sys.exit(23)
            # Creates arg element for instruction in XML
            arg_el = ET.SubElement(opcode_el, f"arg{i + 1}")
            arg_el.set("type", self.token.type.value)
            arg_el.text = self.token.value
            self.token = self.lexer.next()

        # Checks if new line or end of file occures after instruction
        if (self.token.type != TokenType.EOL and
            self.token.type != TokenType.EOF):
            sys.exit(23)

        self.order += 1

    # Checks if current token is correct type
    def _is_arg_valid(self, arg_type):
        if arg_type == "var" and self.token.type == TokenType.VAR:
            return True
        elif arg_type == "symb" and self._is_symbol(self.token.type):
            return True
        elif arg_type == "label" and self.token.type == TokenType.LABEL:
            return True
        elif arg_type == "type" and self.token.type == TokenType.TYPE:
            return True
        return False

    # Checks if given type is symbol
    def _is_symbol(self, type):
        return (type == TokenType.VAR or type == TokenType.INT or
                type == TokenType.BOOL or type == TokenType.STRING or
                type == TokenType.NIL)
