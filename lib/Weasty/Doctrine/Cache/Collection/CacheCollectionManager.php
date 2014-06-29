<?php
namespace Weasty\Doctrine\Cache\Collection;

use Doctrine\Common\Cache\Cache;
use Doctrine\ORM\EntityManager;
use Weasty\Doctrine\EntitySerializer;

/**
 * Class CacheCollectionManager
 * @package Weasty\Doctrine\Cache\Collection
 */
class CacheCollectionManager {

    /**
     * @var array
     */
    protected static $collections = array();

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected static $entityManager;

    /**
     * @var \Weasty\Doctrine\EntitySerializer
     */
    protected static $entitySerializer;

    /**
     * @var \Doctrine\Common\Cache\Cache
     */
    protected static $cache;

    function __construct(EntityManager $entityManager, EntitySerializer $entitySerializer, Cache $cache)
    {
        self::$entityManager = $entityManager;
        self::$entitySerializer = $entitySerializer;
        self::$cache = $cache;
    }

    /**
     * @param $entityClassName
     * @return CacheCollection
     */
    public static function getCollection($entityClassName){

        if(!isset(static::$collections[$entityClassName])){

            $collection = new CacheCollection(
                static::$entityManager,
                static::$entitySerializer,
                static::$cache,
                $entityClassName
            );

            static::$collections[$entityClassName] = $collection;

        }

        return static::$collections[$entityClassName];

    }

} 