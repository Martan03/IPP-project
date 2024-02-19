"""
Author: Martin Slezák - xsleza26

Contains main and basic argument parsing
"""
import sys

from parsing import Parser

def show_help():
    """Prints help"""
    print("""IPPcode24 Parser
Usage: python parse.py [options]

Options:
--help : displays this help and exits

Return codes:
10  : invalid parameters
11  : error reading from stdin
21  : invalid code header
22  : invalid or not known instruction in code
23  : other lexical or syntax error""")

def parse_input():
    """Reads code from stdin and make parser parse it"""
    try:
        text = sys.stdin.read()
    except IOError as _:
        print("error: reading input", file=sys.stderr)
        sys.exit(11)

    # Parses the code
    parser = Parser(text)
    print(parser.parse())

def main():
    """Parses arguments and runs the program"""
    if len(sys.argv) == 1:
        parse_input()
    elif (len(sys.argv) == 2 and
          (sys.argv[1] == "--help" or sys.argv[1] == "-h")):
        show_help()
    else:
        print("error: invalid argument", file=sys.stderr)
        sys.exit(10)

if __name__ == '__main__':
    main()
