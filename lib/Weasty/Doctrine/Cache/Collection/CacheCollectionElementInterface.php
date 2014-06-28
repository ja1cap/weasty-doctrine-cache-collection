<?php

namespace Weasty\Doctrine\Cache\Collection;

/**
 * Interface CacheCollectionElementInterface
 * @package Weasty\Bundle\DoctrineCacheBundle\Collection\Entities
 */
interface CacheCollectionElementInterface extends \ArrayAccess, \JsonSerializable {

    /**
     * @param \Weasty\Doctrine\Cache\Collection\CacheCollection $collection
     * @return $this
     */
    public function setCollection(CacheCollection $collection);

    /**
     * @return \Weasty\Doctrine\Cache\Collection\CacheCollection
     * @throws \Weasty\Doctrine\Cache\Collection\Exception\CacheCollectionException
     */
    public function getCollection();


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
    public function getKey();

    /**
     * @return \Weasty\Doctrine\Entity\AbstractEntity
     */
    public function getEntity();

    /**
     * @param string $entity_identifier_field
     */
    public function setEntityIdentifierField($entity_identifier_field);

    /**
     * @return string
     */
    public function getEntityIdentifierField();

    /**
     * @param string $entity_name
     */
    public function setEntityClassName($entity_name);

    /**
     * @return string
     */
    public function getEntityClassName();

    /**
     * @return array
     */
    public function toArray();

} 