from enum import Enum

# Token types
class TokenType(Enum):
    LABEL = "label"
    # Variable
    VAR = "var"
    INT = "int"
    BOOL = "bool"
    STRING = "string"
    NIL = "nil"
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
