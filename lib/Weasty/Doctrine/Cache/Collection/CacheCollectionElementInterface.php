<?php

namespace Weasty\Doctrine\Cache\Collection;

/**
 * Interface CacheCollectionElementInterface
 * @package Weasty\Doctrine\Cache\Collection
 */
interface CacheCollectionElementInterface extends \ArrayAccess, \JsonSerializable {

    /**
     * @param string|int $cache_id
     */
    public function setCacheId($cache_id);

    /**
     * @return string|int
     */
    public function getCacheId();

    /**
     * @param string|int $key
     */
    public function setKey($key);

    /**
     * @return string|int
     */
    public function getIdentifier();

    /**
     * @return \Weasty\Doctrine\Entity\AbstractEntity
     */
    public function getEntity();

    /**
     * @param string $entityIdentifierField
     */
    public function setEntityIdentifierField($entityIdentifierField);

    /**
     * @return string
     */
    public function getEntityIdentifierField();

    /**
     * @param string $entityName
     */
    public function setEntityClassName($entityName);

    /**
     * @return string
     */
    public function getEntityClassName();

    /**
     * @return array
     */
    public function toArray();

} 