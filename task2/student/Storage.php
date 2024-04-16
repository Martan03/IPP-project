<?php
/**
 * IPP - Storage class
 * @author Martin Slezák - xsleza26
 */

namespace IPP\Student;

use IPP\Student\Exception\FrameAccessException;
use IPP\Student\Exception\SemanticException;
use IPP\Student\Exception\ValueException;
use IPP\Student\Exception\VarAccessException;

/**
 * Class representing storage
 */
class Storage {
    /** @var array<string, StorageItem> global storage frame */
    private array $global;
    /** @var ?array<string, StorageItem> local storage frame */
    private ?array $local;
    /** @var ?array<string, StorageItem> temp storage frame */
    private ?array $temp;

    /** @var array<int, StorageItem> data stack */
    private array $stack;
    /** @var array<int, array<string, StorageItem>> frame stack */
    private array $frameStack;
    /** @var array<int, int> */
    private array $callStack;

    /** @var array<string, int> array containing labels */
    private array $labels;

    /**
     * Constructs new Storage
     */
    public function __construct() {
        $this->global = [];
        $this->local = null;
        $this->temp = null;

        $this->stack = [];
        $this->frameStack = [];
        $this->callStack = [];

        $this->labels = [];
    }

    /**
     * Adds item with name and value to given storage frame
     * @param string $frame storage frame where to store item (GF/LF/TF)
     * @param string $name name of the item in storage
     * @param string $type type of the item
     * @param mixed $value value to be stored to storage
     */
    public function add(
        string $frame,
        string $name,
        ?string $type,
        mixed $value
    ): void {
        $item = new StorageItem($type, $value);
        match ($frame) {
            "GF" => $this->addItem($this->global, $name, $item),
            "LF" => $this->addItem($this->local, $name, $item),
            "TF" => $this->addItem($this->temp, $name, $item),
            default => throw new FrameAccessException(),
        };
    }

    /**
     * Gets value from the memory
     * @param string $frame frame from where to get the value
     * @param string $name name of the item to get value of
     * @return StorageItem value of stored item
     */
    public function get(string $frame, string $name): StorageItem {
        return match ($frame) {
            "GF" => $this->getItem($this->global, $name),
            "LF" => $this->getItem($this->local, $name),
            "TF" => $this->getItem($this->temp, $name),
            default => throw new FrameAccessException(),
        };
    }

    /**
     * Defines variable in given frame
     * @param string $frame frame to define variable in
     * @param string $name name of the variable to define
     */
    public function defVar(string $frame, string $name): void {
        match ($frame) {
            "GF" => $this->defItem($this->global, $name),
            "LF" => $this->defItem($this->local, $name),
            "TF" => $this->defItem($this->temp, $name),
            default => throw new FrameAccessException(),
        };
    }

    /**
     * Pushes item to the stack
     * @param StorageItem $item item to be pushed
     */
    public function push(StorageItem $item): void {
        $this->stack[] = $item;
    }

    /**
     * Pops item from stack to given variable
     * @param string $frame frame variable is in
     * @param string $name name of the variable
     */
    public function pop(string $frame, string $name): void {
        if (empty($this->stack))
            throw new ValueException();

        $item = array_pop($this->stack);
        $this->add($frame, $name, $item->getType(), $item->getValue());
    }

    /**
     * Creates new temp frame
     */
    public function createFrame(): void {
        $this->temp = [];
    }

    /**
     * Pushes temp frame to the stack
     */
    public function pushFrame(): void {
        if (!isset($this->temp))
            throw new FrameAccessException();

        $this->frameStack[] = $this->temp;
        $this->local = &$this->frameStack[count($this->frameStack) - 1];
        $this->temp = null;
    }

    /**
     * Pops temp frame from stack
     */
    public function popFrame(): void {
        if (!isset($this->local))
            throw new FrameAccessException();

        $this->temp = array_pop($this->frameStack);
        $this->local = null;
        if (!empty($this->frameStack))
            $this->local = &$this->frameStack[count($this->frameStack) - 1];
    }

    /**
     * Pushes position to call stack (call instruction)
     * @param int $pos position to be saved
     */
    public function pushCall(int $pos): void {
        $this->callStack[] = $pos;
    }

    /**
     * Pops position from call stack (return instruction)
     * @return int popped position
     */
    public function popCall(): int {
        if (empty($this->callStack))
            throw new ValueException();
        return array_pop($this->callStack);
    }

    /**
     * Adds new label to the labels array
     * @param string $name name of the label
     * @param int $pos position of the label (instruction number)
     * @return bool true on success, else false
     */
    public function addLabel(string $name, int $pos): bool {
        if (isset($this->labels[$name]))
            return false;
        $this->labels[$name] = $pos;
        return true;
    }

    /**
     * Gets label by its name
     * @param string $name name of the label to get
     * @return ?int returns position of found label, else null
     */
    public function getLabel(string $name): int {
        return $this->labels[$name];
    }

    /**
     * Checks whether label exists
     * @param string $name name of the label to check
     * @return bool true when exists, else false
     */
    public function labelExists(string $name): bool {
        return isset($this->labels[$name]);
    }

    /**
     * Checks if item exists in the storage
     * @param string $frame storage frame where to check +;for item
     * @param string $name name of the item to check for
     * @return bool true if exists, else false
     */
    public function exists(string $frame, string $name): bool {
        try {
            $this->getItem($frame, $name);
        } catch (VarAccessException $e) {
            return false;
        }
        return true;
    }

    /**
     * Adds storage item to the storage frame
     * @param array &$frame reference to frame to add item to
     * @param string $name name of the item
     * @param StorageItem $item item to be stored
     */
    private function addItem(
        array &$frame,
        string $name,
        StorageItem $item
    ): void {
        if (!isset($frame))
            throw new FrameAccessException();
        if (!isset($frame[$name]))
            throw new VarAccessException();

        $frame[$name] = $item;
    }

    private function defItem(array &$frame, string $name) {
        if (!isset($frame))
            throw new FrameAccessException();
        if (isset($frame[$name]))
            throw new SemanticException();

        $frame[$name] = new StorageItem(null, null);
    }

    /**
     * Gets item from the given frame
     * @param array &$frame frame to get item from
     * @param string $name name of the item
     * @return StorageItem item from the storage
     */
    public function getItem(array &$frame, string $name): StorageItem {
        if (!isset($frame))
            throw new FrameAccessException();
        if (!isset($frame[$name]))
            throw new VarAccessException();

        return $frame[$name];
    }
}
