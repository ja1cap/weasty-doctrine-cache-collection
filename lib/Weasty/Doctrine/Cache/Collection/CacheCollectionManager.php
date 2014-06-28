<?php
namespace Weasty\Doctrine\Cache\Collection;

use Doctrine\Common\Cache\Cache;
use Doctrine\ORM\EntityManager;

/**
 * Class CacheCollectionManager
 * @package Weasty\Doctrine\Cache\Collection
 */
class CacheCollectionManager {

    /**
     * @var array
     */
    protected $collections = array();

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $entityManager;

    /**
     * @var \Doctrine\Common\Cache\Cache
     */
    protected $cache;

    function __construct(EntityManager $entityManager, Cache $cache)
    {
        $this->entityManager = $entityManager;
        $this->cache = $cache;
    }

    /**
     * @param $entityClassName
     * @return CacheCollection
     */
    public function getCollection($entityClassName){

        if(!isset($this->collections[$entityClassName])){

            $collection = new CacheCollection($this->entityManager, $entityClassName);
            $collection->setCache($this->cache);
            $this->collections[$entityClassName] = $collection;

        }

        return $this->collections[$entityClassName];

    }

} 