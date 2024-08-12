<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2012 Bitrix
 */

namespace Bitrix\Main\ORM\Data;

class AddResult extends Result
{
    /** @var array */
    protected array $primary;

    public function __construct()
    {
        parent::__construct();
    }

    public function setId($id): void
    {
        $this->primary = array('ID' => $id);
    }

    /**
     * Returns id of added record
     * @return int|array
     */
    public function getId(): int|array
    {
        if (count($this->primary) == 1) {
            return end($this->primary);
        }

        return $this->primary;
    }

    /**
     * @param array $primary
     */
    public function setPrimary(array $primary): void
    {
        $this->primary = $primary;
    }

    /**
     * @return array
     */
    public function getPrimary(): array
    {
        return $this->primary;
    }
}
