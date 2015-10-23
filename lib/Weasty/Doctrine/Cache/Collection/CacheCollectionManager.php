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
    protected $collections = array();

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $entityManager;

    /**
     * @var \Weasty\Doctrine\EntitySerializer
     */
    protected $entitySerializer;

    /**
     * @var \Doctrine\Common\Cache\Cache
     */
    protected $cache;

    private static $instance;

    /**
     * @param EntityManager $entityManager
     * @param EntitySerializer $entitySerializer
     * @param Cache $cache
     */
    function __construct(EntityManager $entityManager, EntitySerializer $entitySerializer, Cache $cache)
    {

        $this->entityManager = $entityManager;
        $this->entitySerializer = $entitySerializer;
        $this->cache = $cache;

        self::$instance = $this;

    }

    /**
     * @return CacheCollectionManager
     */
    public static function getInstance(){
        if(!self::$instance){
            //@TODO throw exception
        }
        return self::$instance;
    }

    /**
     * @param $entityClassName
     * @return CacheCollection
     */
    public function getCollection($entityClassName){

        if(!isset($this->collections[$entityClassName])){

            //@TODO change cache time
            $collection = new CacheCollection(
                $this,
                $this->entityManager,
                $this->entitySerializer,
                $this->cache,
                $entityClassName,
                30
            );

            $this->collections[$entityClassName] = $collection;

        }

        return $this->collections[$entityClassName];

    }

} 