"""
Author: Martin Slezák - xsleza26

Parses the code to final XML
- parse function parses the code and returns final XML
"""

import sys
from xml.dom.minidom import parseString
import xml.etree.ElementTree as ET

from token import TokenType
from lexer import Lexer

from constants import INSTRUCTIONS

class Parser:
    """Parser class contains methods for parsing given code"""

    def __init__(self, text):
        self.lexer = Lexer(text)
        self.token = self.lexer.next()
        self.xml = ET.Element("program")
        self.xml.set("language", "IPPcode24")
        self.order = 1

        self.freq = {}
        self.labels = []
        self.back_jmp = 0
        self.fw_jmp = 0
        self.jmp = []

    def parse(self):
        """Parses given text"""
        self._parse_header()

        # Parses tokens until end of file
        self.token = self.lexer.next()
        while self.token.type != TokenType.EOF:
            self._parse_line()
            self.token = self.lexer.next()

        # Finishes stats calculating
        jumps = []
        for jmp in self.jmp:
            if jmp in self.labels:
                self.fw_jmp += 1
            else:
                jumps.append(jmp)
        self.jmp = jumps
        self.freq = dict(sorted(
            self.freq.items(), key=lambda item: item[1], reverse=True)
        )

        # Creates XML code
        dom = parseString(ET.tostring(self.xml, encoding="unicode"))
        xml_bytes = dom.toprettyxml(indent="    ", encoding="UTF-8")
        pretty_xml = xml_bytes.decode('UTF-8')
        return pretty_xml

    # Parses code header
    def _parse_header(self):
        # Skips new lines
        while self.token.type == TokenType.EOL:
            self.token = self.lexer.next()

        # Checks if code contains header
        if self.token.type is not TokenType.HEADER:
            print("error: missing header", file=sys.stderr)
            sys.exit(21)

        # Checks for new line after header or end of file
        self.token = self.lexer.next()
        if self.token.type not in (TokenType.EOL, TokenType.EOF):
            print("error: missing newline after header", file=sys.stderr)
            sys.exit(21)

    # Parses line of code
    def _parse_line(self):
        # Skips new line
        if self.token.type == TokenType.EOL:
            return

        if self.token.type == TokenType.HEADER:
            print("error: duplicate headers", file=sys.stderr)
            sys.exit(23)

        # Line has to start with valid instruction
        opcode = self.token.value.upper()
        if (self.token.type != TokenType.LABEL or
            opcode not in INSTRUCTIONS):
            print(f"error: invalid instruction: {opcode}", file=sys.stderr)
            sys.exit(22)

        # Calculates frequency of opcodes
        if opcode in self.freq:
            self.freq[opcode] += 1
        else:
            self.freq[opcode] = 1

        # Creates instruction element in XML
        opcode_el = ET.SubElement(self.xml, "instruction")
        opcode_el.set("order", str(self.order))
        opcode_el.set("opcode", opcode)

        # Checks argument types
        exp_args = INSTRUCTIONS[opcode]
        self.token = self.lexer.next()
        for i, arg_type in enumerate(exp_args):
            (valid, token_type) = self._is_arg_valid(opcode, arg_type)
            if not valid:
                print("error: invalid argument type", file=sys.stderr)
                sys.exit(23)
            # Creates arg element for instruction in XML
            arg_el = ET.SubElement(opcode_el, f"arg{i + 1}")
            arg_el.set("type", token_type)
            arg_el.text = self.token.value
            self.token = self.lexer.next()

        # Checks if new line or end of file occures after instruction
        if self.token.type not in (TokenType.EOL, TokenType.EOF):
            print("error: no new line after instruction", file=sys.stderr)
            sys.exit(23)

        self.order += 1

    # Checks if current token is correct type
    def _is_arg_valid(self, opcode, arg_type):
        if arg_type == "var" and self.token.type == TokenType.VAR:
            return (True, self.token.type.value)
        if arg_type == "symb" and self._is_symbol(self.token.type):
            return (True, self.token.type.value)
        if arg_type == "label" and self._is_label(self.token.type):
            if opcode in ("JUMP", "JUMPIFEQ", "JUMPIFNEQ"):
                if self.token.value in self.labels:
                    self.back_jmp += 1
                else:
                    self.jmp.append(self.token.value)
            elif opcode == "LABEL":
                if self.token.value not in self.labels:
                    self.labels.append(self.token.value)
            return (True, "label")
        if arg_type == "type" and self.token.type == TokenType.TYPE:
            return (True, self.token.type.value)
        return (False, None)

    # Checks if given type is symbol
    @staticmethod
    def _is_symbol(token_type):
        return token_type in (TokenType.VAR, TokenType.INT, TokenType.BOOL,
                              TokenType.STRING, TokenType.NIL)

    # Checks if given type is label
    @staticmethod
    def _is_label(token_type):
        return token_type in (TokenType.TYPE, TokenType.LABEL)
