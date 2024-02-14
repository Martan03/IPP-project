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
    private array $temp;

    /**
     * Constructs new Storage
     */
    public function __construct() {
        $this->global = [];
        $this->local = [];
        $this->temp = [];
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
        string $type,
        mixed $value
    ): bool {
        return match ($frame) {
            "GF" => $this->addGlobal($name, $type, $value),
            "LF" => $this->addLocal($name, $type, $value),
            "TF" => $this->addTemp($name, $type, $value),
        };
    }

    public function addGlobal(string $name, string $type, mixed $value): bool {
        if (isset($this->global[$name]))
            return false;

        $this->global[$name] = new StorageItem($type, $value);
        return true;
    }

    public function addLocal(string $name, string $type, mixed $value): bool {
        if (isset($this->local[$name]))
            return false;

        $this->local[$name] = new StorageItem($type, $value);
        return true;
    }

    public function addTemp(string $name, string $type, mixed $value): bool {
        if (isset($this->temp[$name]))
            return false;

        $this->temp[$name] = new StorageItem($type, $value);
        return true;
    }
}
