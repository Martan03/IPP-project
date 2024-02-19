"""
Author: Martin Slezák - xsleza26

Contains main and basic argument parsing
"""
import sys

from parsing import Parser

# Displays help
def show_help():
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

# Reads code from stdin and make parser parse it
def parse_input():
    # Gets code from stdin
    try:
        input = sys.stdin.read()
    except IOError as e:
        print("error: reading input", file=sys.stderr)
        sys.exit(11)

    # Parses the code
    parser = Parser(input)
    print(parser.parse())

# Parses arguments and runs the program
def main():
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
