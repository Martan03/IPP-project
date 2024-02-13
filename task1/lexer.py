import re
import sys

from token import Token, TokenType

DATA_TYPES = {"int", "bool", "string", "nil"}
STORE_TYPE = {"GF", "LF", "TF"}
SPEC_CHARS = {'_', '-', '$', '&', '%', '*', '!', '?'}

# Implements lexer
class Lexer:
    def __init__(self, text):
        self.text = text
        self.cur_char = self.text[0] if len(self.text) > 0 else None
        self.current = None
        self.value = ""
        self.pos = 0

    # Gets next token
    def next(self):
        while self.cur_char is not None:
            # New line
            if self.cur_char == '\n':
                self.next_char()
                return Token(TokenType.EOL, '\n')

            # Skips whitespace
            if self.cur_char.isspace():
                self.next_char()
                continue

            # Skips comments
            if self.cur_char == '#':
                self.skip_comment()
                continue

            self.value = ""
            return self.read_literal()

        return Token(TokenType.EOF, '')

    # Gets next char in text
    def next_char(self):
        self.pos += 1
        if len(self.text) > self.pos:
            self.cur_char = self.text[self.pos]
        else:
            self.cur_char = None

    # Skips comments
    def skip_comment(self):
        while self.cur_char is not None and self.cur_char != '\n':
            self.next_char()

    # Reads literal
    def read_literal(self):
        while (self.cur_char is not None and
               not self.cur_char.isspace() and
               self.cur_char != '#'):
            if self.cur_char == '@':
                if self.value in STORE_TYPE:
                    return self.read_var()
                else:
                    return self.read_symb()

            self.value += self.cur_char
            self.next_char()

        if self.value in DATA_TYPES:
            return Token(TokenType.TYPE, self.value)

        if (self.value and not self.value[0].isalpha() and
            self.value[0] not in SPEC_CHARS):
            return Token(TokenType.NALABEL, self.value)

        return Token(TokenType.LABEL, self.value)

    # Reads variable
    def read_var(self):
        self.value += self.cur_char
        self.next_char()

        if not self.cur_char.isalpha() and self.cur_char not in SPEC_CHARS:
            print("error: invalid variable name", file=sys.stderr)
            sys.exit(23)

        self.value += self.cur_char
        self.next_char()
        while (self.cur_char is not None and
               not self.cur_char.isspace() and
               self.cur_char != '#'):
            self.value += self.cur_char
            self.next_char()

        return Token(TokenType.VAR, self.value)

    # Reads symbol
    def read_symb(self):
        type_val = self.value
        self.value = ""
        self.next_char()

        while (self.cur_char is not None and
               not self.cur_char.isspace() and
               self.cur_char != '#'):
            self.value += self.cur_char
            self.next_char()

        type = TokenType.EOF
        valid = True
        if type_val == "string":
            type = TokenType.STRING
        elif type_val == "int":
            valid = self._check_int(self.value)
            type = TokenType.INT
        elif type_val == "bool":
            valid = self._check_bool(self.value)
            type = TokenType.BOOL
        elif type_val == "nil":
            valid = self._check_nil(self.value)
            type = TokenType.NIL
        else:
            print("error: invalid data type: " + type_val, file=sys.stderr)
            sys.exit(23)

        if not valid:
            print(
                "error: invalid symbol value: "  + self.value, file=sys.stderr
            )
            sys.exit(23)

        return Token(type, self.value)

    # Checks if value is bool
    def _check_bool(self, val):
        return val == "true" or val == "false"

    # Checks if value is int
    def _check_int(self, val):
        pattern = r'^([+-]?)(0o[0-7]+|0x[0-9a-fA-F]+|\d+)$'
        return re.match(pattern, val) is not None

    # Checks if value is nil
    def _check_nil(self, val):
        return val == "nil"
