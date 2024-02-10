from enum import Enum

# Token types
class TokenType(Enum):
    # Literal
    LIT = 1
    # Variable
    VAR = 2
    # Symbol
    SYMB = 3
    # End of line
    EOL = 4
    # End of file
    EOF = 5

class Token:
    def __init__(self, type, values=[]):
        self.type = type
        self.values = values
