from enum import Enum

# Token types
class TokenType(Enum):
    # Label
    LABEL = "label"
    # Not allowed label, used for the header
    NALABEL = "nalabel"
    # Variable
    VAR = "var"
    # Symbol tokens
    INT = "int"
    BOOL = "bool"
    STRING = "string"
    NIL = "nil"
    # Type
    TYPE = "type"
    # End of line
    EOL = "eol"
    # End of file
    EOF = "eof"

class Token:
    # Constructs new token of given type with given value
    def __init__(self, type, value):
        self.type = type
        self.value = value
