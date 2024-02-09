# Implements lexer
class Lexer:
    def __init__(self, text):
        self.text = text
        self.current = self.text[0] if len(self.text) > 0 else None
        self.pos = 0

    # Gets next token
    def next(self):
        while self.current is not None:
            # Skips whitespace
            if self.current.isspace():
                self.next_char()
                continue

            if self.current == '#':
                self.skip_comment()
                continue

            self.next_char()

    # Gets next char in text
    def next_char(self):
        self.pos += 1
        if len(self.text) > self.pos:
            self.current = self.text[self.pos]
        else:
            self.current = None

    # Skips comments
    def skip_comment(self):
        while self.current != '\n' and self.current is not None:
            self.next_char()
