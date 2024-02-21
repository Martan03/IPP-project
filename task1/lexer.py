"""
Author: Martin Slezák - xsleza26

Lexer implementation for IPPcode24
- next function gets next token
"""

import re
import sys

from token import Token, TokenType

DATA_TYPES = {"int", "bool", "string", "nil"}
STORE_TYPE = {"GF", "LF", "TF"}

# Regex for matching symbol values
NAME_REX = re.compile(r'^[a-zA-Z_\-$&%*!?][a-zA-Z_\-$&%*!?0-9]*$')
STR_REX = re.compile(r'^([^ #\\]*(\\[0-9][0-9][0-9])*)*$')
INT_REX = re.compile(r'^([+-]?)(0o[0-7]+|0x[0-9a-fA-F]+|\d+)$')

class Lexer:
    """Lexer class breaks given text to tokens"""

    def __init__(self, text):
        self.text = text
        self.cur_char = self.text[0] if len(self.text) > 0 else None
        self.value = ""
        self.pos = 0
        self.header = False
        self.comments = 0

    def next(self):
        """Gets next token"""
        while self.cur_char is not None:
            # New line
            if self.cur_char == '\n':
                self._next_char()
                return Token(TokenType.EOL, '\n')

            # Skips whitespace
            if self.cur_char.isspace():
                self._next_char()
                continue

            # Skips comments
            if self.cur_char == '#':
                self._skip_comment()
                continue

            self.value = ""
            return self._read_literal()

        return Token(TokenType.EOF, '')

    # Gets next char in text
    def _next_char(self):
        self.pos += 1
        if len(self.text) > self.pos:
            self.cur_char = self.text[self.pos]
        else:
            self.cur_char = None

    # Skips comment
    def _skip_comment(self):
        self.comments += 1
        while self.cur_char is not None and self.cur_char != '\n':
            self._next_char()

    # Reads literal
    def _read_literal(self):
        while self._is_end_char(self.cur_char):
            if self.cur_char == '@':
                if self.value in STORE_TYPE:
                    return self._read_var()
                return self._read_symb()

            self.value += self.cur_char
            self._next_char()

        if self.value in DATA_TYPES:
            return Token(TokenType.TYPE, self.value)

        if self.value.lower() == ".ippcode24":
            self.header = True
            return Token(TokenType.HEADER, self.value)

        if NAME_REX.match(self.value):
            return Token(TokenType.LABEL, self.value)

        if self.header:
            print(f"error: unexpected: {self.value}", file=sys.stderr)
            sys.exit(23)
        else:
            print(f"error: invalid header: {self.value}", file=sys.stderr)
            sys.exit(21)

    # Reads variable
    def _read_var(self):
        frame = self.value + self.cur_char
        self.value = ""
        self._next_char()

        while self._is_end_char(self.cur_char):
            self.value += self.cur_char
            self._next_char()

        if NAME_REX.match(self.value):
            return Token(TokenType.VAR, frame + self.value)

        print(f"error: invalid variable name: {self.value}", file=sys.stderr)
        sys.exit(23)

    # Reads symbol
    def _read_symb(self):
        type_val = self.value
        self.value = ""
        self._next_char()

        while self._is_end_char(self.cur_char):
            self.value += self.cur_char
            self._next_char()

        if type_val == "string":
            valid = STR_REX.match(self.value)
            token_type = TokenType.STRING
        elif type_val == "int":
            valid = INT_REX.match(self.value)
            token_type = TokenType.INT
        elif type_val == "bool":
            valid = self.value in ("true", "false")
            token_type = TokenType.BOOL
        elif type_val == "nil":
            valid = self.value == "nil"
            token_type = TokenType.NIL
        else:
            print(f"error: invalid data type: {type_val}", file=sys.stderr)
            sys.exit(23)

        if not valid:
            print(
                f"error: invalid {type_val} value: {self.value}",
                file=sys.stderr
            )
            sys.exit(23)

        return Token(token_type, self.value)

    @staticmethod
    def _is_end_char(val):
        return val is not None and not val.isspace() and val != '#'
