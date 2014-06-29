<?php

namespace Weasty\Doctrine\Cache\Collection;

/**
 * Interface CacheCollectionEntityInterface
 * @package Weasty\Bundle\DoctrineCacheBundle\Collection\Entities
 */
interface CacheCollectionEntityInterface extends \ArrayAccess {

    /**
     * @return string
     */
    public function getIdentifierField();

    /**
     * @return mixed
     */
    public function getIdentifier();

    /**
     * @param $collection \Weasty\Doctrine\Cache\Collection\CacheCollection
     * @return \Weasty\Doctrine\Cache\Collection\CacheCollectionElementInterface
     */
    public function createCollectionElement($collection);

} 
