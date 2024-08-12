<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2012 Bitrix
 */

namespace Bitrix\Main\ORM\Data;

use Bitrix\Main\DB\Connection;

class UpdateResult extends Result
{
    /** @var int */
    protected int $affectedRowsCount;

    /** @var array */
    protected array $primary;

    public function __construct()
    {
        parent::__construct();
    }

    public function setAffectedRowsCount(Connection $connection): void
    {
        $this->affectedRowsCount = $connection->getAffectedRowsCount();
    }

    /**
     * @return int
     */
    public function getAffectedRowsCount(): int
    {
        return $this->affectedRowsCount;
    }

    public function setPrimary($primary): void
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

    /**
     * Returns id of updated record
     * @return array|int|string
     */
    public function getId(): array|int|string
    {
        if (count($this->primary) == 1) {
            return end($this->primary);
        }

        return $this->primary;
    }
}
