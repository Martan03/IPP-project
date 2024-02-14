<?php
/**
 * IPP - StorageItem class
 */

namespace IPP\Student;

/**
 * Class that defines Storage item
 */
class StorageItem {
    private ?string $type;
    private mixed $value;

    /**
     * Constructs new Storage item
     * @param string $type type of the item
     * @param string $value value of the item
     */
    public function __construct(?string $type, mixed $value) {
        $this->type = $type;
        $this->value = $value;
    }

    /**
     * Gets type of the storage item
     * @return string storage item type
     */
    public function getType(): ?string {
        return $this->type;
    }

    /**
     * Gets value of the item
     * @return mixed storage item value
     */
    public function getValue(): mixed {
        return $this->value;
    }
}
