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
     * @return \Weasty\Doctrine\Cache\Collection\CacheCollectionElementInterface
     */
    public function createCollectionElement();

    /**
     * @return \Weasty\Doctrine\Cache\Collection\CacheCollectionElementInterface
     */
    public function getCollectionElement();

} 