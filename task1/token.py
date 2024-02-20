"""
Author: Martin Slezák - xsleza26

Tokens definition
"""

from enum import Enum

class TokenType(Enum):
    """Token enumaration contains all token types"""

    # Label
    LABEL = "label"
    # Symbol for header
    HEADER = "header"
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
    """Token class containing type as TokenType and value of the token"""

    # Constructs new token of given type with given value
    def __init__(self, token_type, value):
        self.type = token_type
        self.value = value
