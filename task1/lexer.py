import re
import sys

DATA_TYPES = {"int", "bool", "string"}
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
            if self.cur_char == '\n':
                print("Newline")
                self.next_char()
                continue

            # Skips whitespace
            if self.cur_char.isspace():
                self.next_char()
                continue

            # Skips comments
            if self.cur_char == '#':
                self.skip_comment()
                continue

            self.value = ""
            self.read_literal()

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
        while self.cur_char is not None and not self.cur_char.isspace():
            if self.cur_char == '@':
                if self.value in DATA_TYPES:
                    return self.read_symb()
                elif self.value in STORE_TYPE:
                    return self.read_var()
                else:
                    # TODO: fix error message and error code
                    sys.stderr.write("Invalid type")
                    sys.exit(1)

            self.value += self.cur_char
            self.next_char()

        print("Literal: " + self.value)

    # Reads variable
    def read_var(self):
        type = self.value
        self.next_char()

        if not self.cur_char.isalpha() and self.cur_char not in SPEC_CHARS:
            sys.stderr.write("Invalid variable name")
            sys.exit(1)

        self.value = self.cur_char
        self.next_char()
        while self.cur_char is not None and not self.cur_char.isspace():
            self.value += self.cur_char
            self.next_char()

        print("VarType: " + type + ", value: " + self.value)

    def read_symb(self):
        type = self.value
        self.next_char()

        self.value = ""
        while self.cur_char is not None and not self.cur_char.isspace():
            self.value += self.cur_char
            self.next_char()

        print("SymbType: " + type + ", value: " + self.value)
