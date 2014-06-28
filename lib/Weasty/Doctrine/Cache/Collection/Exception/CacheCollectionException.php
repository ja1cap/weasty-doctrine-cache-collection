<?php
namespace Weasty\Doctrine\Cache\Collection\Exception;

use \Exception;

/**
 * Class CacheCollectionException
 * @package Weasty\Doctrine\Cache\Collection\Exception
 */
class CacheCollectionException extends Exception {

    /**
     * @param string $message
     * @param int $code
     * @param Exception $previous
     */
    public function __construct($message = 'Entities collection error', $code = 500, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return CacheCollectionException
     */
    public static function elementEntityNotFound(){
        return new self('Element entity not found');
    }

    /**
     * @return CacheCollectionException
     */
    public static function undefinedElementEntityName(){
        return new self('Set CacheCollectionElementInterface::$entityClassName');
    }

    /**
     * @return CacheCollectionException
     */
    public static function undefinedElementIdentifierField(){
        return new self('Set CacheCollectionElementInterface::$entityIdentifierField');
    }

    /**
     * @return CacheCollectionException
     */
    public static function undefinedElementKey(){
        return new self('Set CacheCollectionElementInterface::$key');
    }

    /**
     * @return CacheCollectionException
     */
    public static function invalidElement(){
        return new self('Entities collection element must implement CacheCollectionElementInterface or CacheCollectionEntityInterface');
    }

    /**
     * @return CacheCollectionException
     */
    public static function invalidEntityLazyElement(){
        return new self('CacheCollectionEntityInterface::createLazyElement must return CacheCollectionElementInterface instance');
    }

}