<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2012 Bitrix
 */

namespace Bitrix\Main\ORM\Data;

use Bitrix\Main\ORM\EntityError;
use Bitrix\Main\ORM\Fields\FieldError;
use Bitrix\Main\ORM\Objectify\EntityObject;

class Result extends \Bitrix\Main\Result
{
    /** @var bool */
    protected bool $wereErrorsChecked = false;

    /** @var EntityObject */
    protected EntityObject $object;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @return EntityObject
     */
    public function getObject(): EntityObject
    {
        return $this->object;
    }

    /**
     * @param EntityObject $object
     */
    public function setObject(EntityObject $object)
    {
        $this->object = $object;
    }

    /**
     * Returns result status
     * Within the core and events should be called with internalCall flag
     *
     * @param bool $internalCall
     *
     * @return bool
     */
    public function isSuccess(bool $internalCall = false): bool
    {
        if (!$internalCall && !$this->wereErrorsChecked) {
            $this->wereErrorsChecked = true;
        }

        return parent::isSuccess();
    }

    /**
     * Returns an array of Error objects
     *
     * @return EntityError[]|FieldError[]
     */
    public function getErrors(): array
    {
        $this->wereErrorsChecked = true;

        return parent::getErrors();
    }

    /**
     * Returns array of strings with error messages
     *
     * @return array
     */
    public function getErrorMessages(): array
    {
        $this->wereErrorsChecked = true;

        return parent::getErrorMessages();
    }

    public function __destruct()
    {
        if (!$this->isSuccess && !$this->wereErrorsChecked) {
            // nobody interested in my errors :(
            // make a warning (usually it should be written in log)
            trigger_error(join('; ', $this->getErrorMessages()), E_USER_WARNING);
        }
    }
}
