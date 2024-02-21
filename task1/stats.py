from enum import Enum

class StatType(Enum):
    LOC = 1
    COMMENT = 2
    LABEL = 3
    JMP = 4
    FW_JMP = 5
    BACK_JMP = 6
    BAD_JMP = 7
    FREQ = 8
    PRINT = 9
    EOL = 10

class Stats:
    def __init__(self, file):
        self.file = file
        self.args = []
        self.text = []

    def add_arg(self, arg):
        self.args.append(arg)

    def add_print(self, text):
        self.args.append(StatType.PRINT)
        self.text.append(text)

    def print(self, file, parser):
        for arg in self.args:
            match arg:
                case StatType.LOC:
                    print(parser.order - 1, file=file)
                case StatType.COMMENT:
                    print(parser.lexer.comments, file=file)
                case StatType.LABEL:
                    print(len(parser.labels), file=file)
                case StatType.JMP:
                    print(
                        len(parser.jmp) + parser.back_jmp + parser.fw_jmp,
                        file=file
                    )
                case StatType.FW_JMP:
                    print(parser.fw_jmp, file=file)
                case StatType.BACK_JMP:
                    print(parser.back_jmp, file=file)
                case StatType.BAD_JMP:
                    print(len(parser.jmp), file=file)
                case StatType.FREQ:
                    ()
                case StatType.PRINT:
                    print(self.text.pop(0), file=file)
                case StatType.EOL:
                    print("", file=file)
