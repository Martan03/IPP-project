<?php
/**
 * IPP - Storage class
 * @author Martin Slezák - xsleza26
 */

namespace IPP\Student;

use IPP\Student\Exception\FrameAccessException;

/**
 * Class representing storage
 */
class Storage {
    /**
     * @var array<string, StorageItem> global storage frame
     */
    private array $global;
    /**
     * @var array<string, StorageItem> local storage frame
     */
    private array $local;
    /**
     * @var ?array<string, StorageItem> temp storage frame
     */
    private ?array $temp;
    /**
     * @var array<int, array<string, StorageItem>> temp frames queue
     */
    private array $queue;

    /**
     * @var array<string, int> array containing labels
     */
    private array $labels;

    /**
     * Constructs new Storage
     */
    public function __construct() {
        $this->global = [];
        $this->local = [];
        $this->temp = null;
        $this->queue = [];

        $this->labels = [];
    }

    /**
     * Adds item with name and value to given storage frame
     * @param string $frame storage frame where to store item (GF/LF/TF)
     * @param string $name name of the item in storage
     * @param string $type type of the item
     * @param mixed $value value to be stored to storage
     * @return bool true on success, else false
     */
    public function add(
        string $frame,
        string $name,
        ?string $type,
        mixed $value
    ): bool {
        if (!$this->exists($frame, $name))
            return false;

        match ($frame) {
            "GF" => $this->global[$name] = new StorageItem($type, $value),
            "LF" => $this->local[$name] = new StorageItem($type, $value),
            "TF" => $this->temp[$name] = new StorageItem($type, $value),
            default => throw new FrameAccessException(),
        };
        return true;
    }

    /**
     * Gets value from the memory
     * @param string $frame frame from where to get the value
     * @param string $name name of the item to get value of
     * @return ?StorageItem value of stored item
     */
    public function get(string $frame, string $name): ?StorageItem {
        return match ($frame) {
            "GF" => $this->global[$name],
            "LF" => $this->local[$name],
            "TF" => $this->temp[$name],
            default => throw new FrameAccessException(),
        };
    }

    /**
     * Defines variable in given frame
     * @param string $frame frame to define variable in
     * @param string $name name of the variable to define
     * @return bool true on success, else false
     */
    public function defVar(string $frame, string $name): bool {
        if ($this->exists($frame, $name))
            return false;

        match ($frame) {
            "GF" => $this->global[$name] = new StorageItem(null, null),
            "LF" => $this->local[$name] = new StorageItem(null, null),
            "TF" => $this->temp[$name] = new StorageItem(null, null),
            default => throw new FrameAccessException(),
        };
        return true;
    }

    /**
     * Creates new temp frame
     */
    public function create(): void {
        $this->temp = [];
    }

    /**
     * Pushes temp frame to the queue
     */
    public function push(): void {
        $this->queue[] = $this->temp;
        $this->temp = null;
    }

    /**
     * Pops temp frame from queue
     */
    public function pop(): void {
        $this->temp = array_shift($this->queue);
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
    public function getLabel(string $name): ?int {
        return $this->labels[$name];
    }

    /**
     * Checks if item exists in the storage
     * @param string $frame storage frame where to check for item
     * @param string $name name of the item to check for
     * @return bool true if exists, else false
     */
    public function exists(string $frame, string $name): bool {
        return match ($frame) {
            "GF" => isset($this->global[$name]),
            "LF" => isset($this->local[$name]),
            "TF" => isset($this->temp[$name]),
            default => throw new FrameAccessException(),
        };
    }
}
