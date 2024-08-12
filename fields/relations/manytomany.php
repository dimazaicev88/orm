<?php
/**
 * Bitrix Framework
 * @package    bitrix
 * @subpackage main
 * @copyright  2001-2018 Bitrix
 */

namespace Bitrix\Main\ORM\Fields\Relations;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ORM\Entity;
use Bitrix\Main\ORM\Fields\FieldTypeMask;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\SystemException;
use Bitrix\Main\Text\StringHelper;

/**
 * Performs many to many relation through mediator entity
 * @package    bitrix
 * @subpackage main
 */
class ManyToMany extends Relation
{
    /** @var string */
    protected string $mediatorEntityName;

    /** @var Entity */
    protected Entity $mediatorEntity;

    /** @var string Used when mediator is a virtual entity */
    protected string $mediatorTableName;

    /** @var string[] Stores owner entity primary => mediator primary */
    protected array $localPrimaryNames;

    /** @var string Name of reference from mediator to owner entity */
    protected string $localReferenceName;

    /** @var string[] Stores target entity primary => mediator primary */
    protected array $remotePrimaryNames;

    /** @var string Name of reference from mediator to target entity */
    protected string $remoteReferenceName;

    /** @var string */
    protected ?string $joinType = Join::TYPE_LEFT;

    /** @var int */
    protected int $cascadeSavePolicy = CascadePolicy::NO_ACTION;

    protected int $cascadeDeletePolicy = CascadePolicy::NO_ACTION; // follow_orphans | no_action

    /**
     * @param string $name
     * @param string|Entity $referenceEntity
     *
     * @throws SystemException
     */
    public function __construct($name, $referenceEntity)
    {
        if ($referenceEntity instanceof Entity) {
            $this->refEntity = $referenceEntity;
            $this->refEntityName = $referenceEntity->getFullName();
        } else {
            // this one could be without leading backslash and/or with Table-postfix
            $this->refEntityName = Entity::normalizeName($referenceEntity);
        }

        parent::__construct($name);
    }

    public function getTypeMask(): int
    {
        return FieldTypeMask::MANY_TO_MANY;
    }

    /**
     * Explicit mediator entity. By default, will be generated automatically.
     *
     * @param string|Entity $entity
     *
     * @return $this
     */
    public function configureMediatorEntity(Entity|string $entity): static
    {
        if ($entity instanceof Entity) {
            $this->mediatorEntity = $entity;
            $this->mediatorEntityName = $entity->getFullName();
        } else {
            // this one could be without leading backslash and/or with Table-postfix
            $this->mediatorEntityName = Entity::normalizeName($entity);
        }

        return $this;
    }

    /**
     * In case of auto-generated mediator, sets the custom table name.
     *
     * @param $name
     *
     * @return $this
     */
    public function configureMediatorTableName($name): static
    {
        $this->mediatorTableName = $name;

        return $this;
    }

    /**
     * Short alias for configureMediatorTableName()
     *
     * @param $name
     *
     * @return $this
     */
    public function configureTableName($name): static
    {
        return $this->configureMediatorTableName($name);
    }

    /**
     * In case of auto-generated mediator, sets the custom ID field name that stores owner entity ID.
     *
     * @param $fieldName
     * @param $mediatorFieldName
     *
     * @return $this
     */
    public function configureLocalPrimary($fieldName, $mediatorFieldName): static
    {
        $this->localPrimaryNames[$fieldName] = $mediatorFieldName;

        return $this;
    }

    /**
     * In case of auto-generated mediator, sets the custom reference field name that points to owner entity.
     *
     * @param $name
     *
     * @return $this
     */
    public function configureLocalReference($name): static
    {
        $this->localReferenceName = $name;

        return $this;
    }

    /**
     * In case of auto-generated mediator, sets the custom ID field name that stores target entity ID.
     *
     * @param $fieldName
     * @param $mediatorFieldName
     *
     * @return $this
     */
    public function configureRemotePrimary($fieldName, $mediatorFieldName): static
    {
        $this->remotePrimaryNames[$fieldName] = $mediatorFieldName;

        return $this;
    }

    /**
     * In case of auto-generated mediator, sets the custom reference field name that points to target entity.
     *
     * @param $name
     *
     * @return $this
     */
    public function configureRemoteReference($name): static
    {
        $this->remoteReferenceName = $name;

        return $this;
    }

    /**
     * @return Entity
     * @throws ArgumentException
     * @throws SystemException
     */
    public function getRemoteEntity(): Entity
    {
        return $this->getRefEntity();
    }

    /**
     * @return Entity
     * @throws ArgumentException
     * @throws SystemException
     */
    public function getMediatorEntity(): Entity
    {
        if ($this->mediatorEntity === null) {
            if (!empty($this->mediatorEntityName) && Entity::has($this->mediatorEntityName)) {
                $this->mediatorEntity = Entity::getInstance($this->mediatorEntityName);
            } else {
                // there is no described mediator entity
                // check table_name first, entity can not exist without it
                if (empty($this->mediatorTableName)) {
                    throw new ArgumentException(sprintf(
                        'Table Name for mediator entity of relation `%s` between %s and %s was not found',
                        $this->name, $this->getEntity()->getObjectClass(), $this->getRefEntity()->getObjectClass()
                    ));
                }

                // generate mediator entity runtime
                if (empty($this->mediatorEntityName)) {
                    $localEntityName = $this->getEntity()->getName();
                    $remoteEntityName = $this->getRefEntity()->getName();
                    $fieldToClassName = StringHelper::snake2camel($this->name);

                    // each field has its own entity in case of ManyToMany definitions will be different
                    $this->mediatorEntityName = "MediatorFrom{$localEntityName}To{$remoteEntityName}Via{$fieldToClassName}Table";
                }

                // fields of mediator entity
                $fields = [];

                // local entity primary
                $localReferenceConditions = Query::filter();

                foreach ($this->getEntity()->getPrimaryArray() as $primaryName) {
                    $mediatorPrimaryName = $this->localPrimaryNames[$primaryName] ?? $this->getLocalReferenceName() . '_' . $primaryName;

                    $fieldType = get_class($this->getEntity()->getField($primaryName));

                    /** @var \Bitrix\Main\ORM\Fields\ScalarField $mediatorPrimary */
                    $mediatorPrimary = new $fieldType($mediatorPrimaryName);
                    $mediatorPrimary->configurePrimary(true);

                    $fields[] = $mediatorPrimary;

                    // save join condition for reference
                    $localReferenceConditions->whereColumn('this.' . $mediatorPrimaryName, 'ref.' . $primaryName);
                }

                // local reference
                $localReference = (new Reference($this->getLocalReferenceName(), $this->getEntity(), $localReferenceConditions))
                    ->configureJoinType($this->joinType);
                $fields[] = $localReference;

                // remote entity primary
                $remoteReferenceConditions = Query::filter();

                foreach ($this->getRefEntity()->getPrimaryArray() as $primaryName) {
                    $mediatorPrimaryName = $this->remotePrimaryNames[$primaryName] ?? $this->getRemoteReferenceName() . '_' . $primaryName;

                    $fieldType = get_class($this->getRefEntity()->getField($primaryName));

                    /** @var \Bitrix\Main\ORM\Fields\ScalarField $mediatorPrimary */
                    $mediatorPrimary = new $fieldType($mediatorPrimaryName);
                    $mediatorPrimary->configurePrimary(true);

                    $fields[] = $mediatorPrimary;

                    // save join condition for reference
                    $remoteReferenceConditions->whereColumn('this.' . $mediatorPrimaryName, 'ref.' . $primaryName);
                }

                // remote reference
                $remoteReference = (new Reference($this->getRemoteReferenceName(), $this->getRefEntity(), $remoteReferenceConditions))
                    ->configureJoinType($this->joinType);
                $fields[] = $remoteReference;

                // set table name
                $parameters = ['table_name' => $this->mediatorTableName];

                // finalize
                $this->mediatorEntity = Entity::compileEntity($this->mediatorEntityName, $fields, $parameters);
            }
        }

        return $this->mediatorEntity;
    }

    /**
     * @return string
     */
    public function getLocalReferenceName(): string
    {
        if (empty($this->localReferenceName)) {
            $this->localReferenceName = strtoupper(StringHelper::camel2snake($this->getEntity()->getName()));
        }

        return $this->localReferenceName;
    }

    /**
     * Returns reference from mediator to owner entity
     *
     * @return \Bitrix\Main\ORM\Fields\Relations\Reference|\Bitrix\Main\ORM\Fields\Field
     * @throws ArgumentException
     * @throws SystemException
     */
    public function getLocalReference()
    {
        return $this->getMediatorEntity()->getField($this->getLocalReferenceName());
    }

    /**
     * @return string
     * @throws ArgumentException
     * @throws SystemException
     */
    public function getRemoteReferenceName(): string
    {
        if (empty($this->remoteReferenceName)) {
            $this->remoteReferenceName = strtoupper(StringHelper::camel2snake($this->getRefEntity()->getName()));
        }

        return $this->remoteReferenceName;
    }

    /**
     * Returns reference from mediator to target entity
     *
     * @return \Bitrix\Main\ORM\Fields\Relations\Reference|\Bitrix\Main\ORM\Fields\Field
     * @throws ArgumentException
     * @throws SystemException
     */
    public function getRemoteReference()
    {
        return $this->getMediatorEntity()->getField($this->getRemoteReferenceName());
    }
}
