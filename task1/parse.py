import argparse
import sys

from lexer import Lexer
from token import TokenType

from parsing import Parser

# Gets return codes for help
def get_ret_codes():
    return """Return codes:
0-9 : correct execution
10  : invalid parameters
11  : input file error
12  : output file error
31  : invalid source XML format
32  : invalid source structure
52  : semantic error
53  : runtime error - bad operand types
54  : runtime error - non-existent variable
55  : runtime error - non-existent frame
56  : runtime error - missing value
57  : runtime error - bad operand value
58  : runtime error - bad string operation
88  : integration error
99  : internal error
"""

# Parses arguments and runs the program
def main():
    parser = argparse.ArgumentParser(
        description='IPPcode24 Interpreter',
        epilog=get_ret_codes(),
        formatter_class=argparse.RawDescriptionHelpFormatter
    )
    parser.parse_args()

    input = sys.stdin.read()
    parser = Parser(input)
    print(parser.parse())

if __name__ == '__main__':
    main()
