"""
Author: Martin Slezák - xsleza26

Contains main and basic argument parsing
"""
import sys

from parsing import Parser
from stats import StatType, Stats

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

def add_arg(stat, arg):
    if stat is None:
        print("error: invalid usage", file=sys.stderr)
        sys.exit(10)

    stat.add_arg(arg)

def add_stat(stats, stat):
    if stat is None:
        return

    if stat.file in stats:
        print("error: multiple stats to one file", file=sys.stderr)
        sys.exit(12)

    stats[stat.file] = stat

def parse_args(args):
    args.pop(0)
    help = False
    stats = {}
    stat = None
    for arg in args:
        match arg:
            case "--help":
                help = True
            case "--loc":
                add_arg(stat, StatType.LOC)
            case "--comments":
                add_arg(stat, StatType.COMMENT)
            case "--labels":
                add_arg(stat, StatType.LABEL)
            case "--jumps":
                add_arg(stat, StatType.JMP)
            case "--fwjumps":
                add_arg(stat, StatType.FW_JMP)
            case "--backjumps":
                add_arg(stat, StatType.BACK_JMP)
            case "--badjumps":
                add_arg(stat, StatType.BAD_JMP)
            case "--frequent":
                add_arg(stat, StatType.FREQ)
            case "eol":
                add_arg(stat, StatType.EOL)
            case _:
                if arg.startswith("--stats="):
                    add_stat(stats, stat)
                    split_str = arg.split('=', 1)
                    if len(split_str) < 2:
                        sys.exit(10)
                    stat = Stats(split_str[1])
                elif arg.startswith("--print="):
                    split_str = arg.split('=', 1)
                    val = ""
                    if len(split_str) == 2:
                        val = split_str[1]

                    if stat is None:
                        print("error: invalid usage", file=sys.stderr)
                        sys.exit(10)

                    stat.add_print(val)
                else:
                    print("error: invalid argument", file=sys.stderr)
                    sys.exit(10)

    if help:
        if len(args) > 1:
            print(
                "error: help cannot be used with other arguments",
                file=sys.stderr
            )
            sys.exit(10)
        show_help()
        sys.exit(0)

    add_stat(stats, stat)
    return stats

def print_stats(parser, stats):
    for stat in stats.values():
        try:
            file = open(stat.file, "w")
        except OSError as _:
            print("error: failed to open file: " + stat.file, file=sys.stderr)
            sys.exit(12)

        with file:
            stat.print(file, parser)

def main():
    """Parses arguments and runs the program"""
    stats = parse_args(sys.argv)

    try:
        text = sys.stdin.read()
    except IOError as _:
        print("error: reading input", file=sys.stderr)
        sys.exit(11)

    # Parses the code
    parser = Parser(text)
    print(parser.parse())

    print_stats(parser, stats)

if __name__ == '__main__':
    main()
