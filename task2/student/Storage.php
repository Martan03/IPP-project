<?php
/**
 * IPP - Storage class
 * @author Martin Slezák - xsleza26
 */

namespace IPP\Student;

/**
 * Class representing storage
 */
class Storage {
    private array $global;
    private array $local;
    private ?array $temp;
    private array $queue;

    /**
     * Constructs new Storage
     */
    public function __construct() {
        $this->global = [];
        $this->local = [];
        $this->temp = null;
        $this->queue = [];
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
        return match ($frame) {
            "GF" => $this->addGlobal($name, $type, $value),
            "LF" => $this->addLocal($name, $type, $value),
            "TF" => $this->addTemp($name, $type, $value),
        };
    }

    /**
     * Gets value from the memory
     * @param string $frame frame from where to get the value
     * @param string $name name of the item to get value of
     * @return mixed value of stored item
     */
    public function get(string $frame, string $name): StorageItem {
        return match ($frame) {
            "GF" => $this->getGlobal($name),
            "LF" => $this->getLocal($name),
            "TF" => $this->getTemp($name),
        };
    }

    /**
     * Defines variable in given frame
     * @param string $frame frame to define variable in
     * @param string $name name of the variable to define
     * @return bool true on success, else false
     */
    public function defVar(string $frame, string $name): bool {
        return $this->add($frame, $name, null, null);
    }

    /**
     * Creates new temp frame
     */
    public function create() {
        $this->temp = [];
    }

    /**
     * Pushes temp frame to the queue
     */
    public function push() {
        $this->queue[] = $this->temp;
        $this->temp = null;
    }

    /**
     * Pops temp frame from queue
     */
    public function pop() {
        $this->temp = array_shift($this->queue);
    }

    private function addGlobal(
        string $name,
        ?string $type,
        mixed $value
    ): bool {
        if (isset($this->global[$name]))
            return false;

        $this->global[$name] = new StorageItem($type, $value);
        return true;
    }

    private function addLocal(
        string $name,
        ?string $type,
        mixed $value
    ): bool {
        if (isset($this->local[$name]))
            return false;

        $this->local[$name] = new StorageItem($type, $value);
        return true;
    }

    private function addTemp(string $name, ?string $type, mixed $value): bool {
        if (isset($this->temp[$name]))
            return false;

        $this->temp[$name] = new StorageItem($type, $value);
        return true;
    }

    private function getGlobal(string $name): StorageItem {
        return $this->global[$name];
    }

    private function getLocal(string $name): StorageItem {
        return $this->local[$name];
    }

    private function getTemp(string $name): StorageItem {
        return $this->temp[$name];
    }
}
