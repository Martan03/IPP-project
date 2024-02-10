import argparse
import sys

from lexer import Lexer
from token import TokenType

# Parses arguments and runs the program
def main():
    parser = argparse.ArgumentParser(
        description='IPPcode24 Interpreter',
        epilog='Return codes:\n'
            '0-9 : correct execution\n'
            '10  : invalid parameters\n'
            '11  : input file error\n'
            '12  : output file error\n'
            '31  : invalid source XML format\n'
            '32  : invalid source structure\n'
            '52  : semantic error\n'
            '53  : runtime error - bad operand types\n'
            '54  : runtime error - non-existent variable\n'
            '55  : runtime error - non-existent frame\n'
            '56  : runtime error - missing value\n'
            '57  : runtime error - bad operand value\n'
            '58  : runtime error - bad string operation\n'
            '88  : integration error\n'
            '99  : internal error\n',
        formatter_class=argparse.RawDescriptionHelpFormatter
    )
    parser.parse_args()

    input = sys.stdin.read()
    lexer = Lexer(input)

    token = lexer.next()
    while token.type != TokenType.EOF:
        print(token.type, end=' ')
        for val in token.values:
            print(val, end=' ')
        print()
        token = lexer.next()

if __name__ == '__main__':
    main()
