<?php
namespace Weasty\Doctrine\Cache\Collection;

use Weasty\Doctrine\Entity\EntityInterface;
use Weasty\Doctrine\Cache\Collection\Exception\CacheCollectionException;

/**
 * Class CacheCollectionElement
 * @package Weasty\Doctrine\Cache\Collection
 */
class CacheCollectionElement implements CacheCollectionElementInterface {

    /**
     * @var string|int
     */
    public $key;

    /**
     * @var string|int
     */
    public $cacheId;

    /**
     * @var string
     */
    public $__string;

    /**
     * @var array
     */
    public $data = array();

    /**
     * @var string
     */
    public $entityIdentifierField;

    /**
     * @var string
     */
    public $entityClassName;

    /**
     * @var \Weasty\Doctrine\Entity\EntityInterface
     */
    protected $entity;

    /**
     * @var \Weasty\Doctrine\Cache\Collection\CacheCollection
     */
    private $collection;

    function __construct(CacheCollection $collection, EntityInterface $entity)
    {

        $this->entity = $entity;
        $this->collection = $collection;

        $this->__string = (string)$entity;

        $data = $this->buildData($collection, $entity);
        $this->setData($data);

    }

    /**
     * @param int|string $key
     */
    public function setKey($key)
    {
        $this->key = $key;
    }

    /**
     * @return string|int
     */
    public function getIdentifier()
    {
        return $this->key ?: $this->getEntity()->getIdentifier();
    }

    /**
     * @param int|string $cache_id
     */
    public function setCacheId($cache_id)
    {
        $this->cacheId = $cache_id;
    }

    /**
     * @return int|string
     */
    public function getCacheId()
    {
        return $this->cacheId;
    }

    /**
     * @param CacheCollection $collection
     * @param EntityInterface $entity
     * @return array
     */
    protected function buildData(CacheCollection $collection, EntityInterface $entity){
        return $collection->getEntitySerializer()->toArray($entity);
    }

    /**
     * @param array $data
     * @return $this
     */
    protected function setData($data)
    {
        $this->data = $data;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @return \Weasty\Doctrine\Entity\EntityInterface
     * @throws \Weasty\Doctrine\Cache\Collection\Exception\CacheCollectionException
     */
    public function getEntity()
    {

        if(!$this->entity){

            if(!$this->getIdentifier()){
                throw CacheCollectionException::undefinedElementIdentifier();
            }

            if(!$this->getEntityIdentifierField()){
                throw CacheCollectionException::undefinedElementIdentifierField();
            }

            $this->entity = $this->getRepository()->findOneBy(array(
                $this->getEntityIdentifierField() => $this->getIdentifier(),
            ));

            if(!$this->entity){
                throw CacheCollectionException::elementEntityNotFound();
            }

        }

        return $this->entity;

    }

    /**
     * @param string $entityIdentifierField
     */
    public function setEntityIdentifierField($entityIdentifierField)
    {
        $this->entityIdentifierField = $entityIdentifierField;
    }

    /**
     * @return string
     */
    public function getEntityIdentifierField()
    {
        return $this->entityIdentifierField;
    }

    /**
     * @param string $entity_class_name
     */
    public function setEntityClassName($entity_class_name)
    {
        $this->entityClassName = $entity_class_name;
    }

    /**
     * @return string
     */
    public function getEntityClassName()
    {
        return $this->entityClassName;
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Whether a offset exists
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $offset <p>
     * An offset to check for.
     * </p>
     * @return boolean true on success or false on failure.
     * </p>
     * <p>
     * The return value will be casted to boolean if non-boolean was returned.
     */
    public function offsetExists($offset)
    {

        $classifiedOffset = str_replace(" ", "", ucwords(strtr($offset, "_-", "  ")));
        $method = 'get' . $classifiedOffset;
        if(method_exists($this, $method)){
            return true;
        }

        return isset($this->data[$offset]);

    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to retrieve
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset <p>
     * The offset to retrieve.
     * </p>
     * @return mixed Can return all value types.
     */
    public function offsetGet($offset)
    {

        $classifiedOffset = str_replace(" ", "", ucwords(strtr($offset, "_-", "  ")));
        $method = 'get' . $classifiedOffset;

        if(method_exists($this, $method)){
            return $this->$method();
        }

        if(!isset($this->data[$offset])){
            return null;
        }

        return $this->data[$offset];

    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to set
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $offset <p>
     * The offset to assign the value to.
     * </p>
     * @param mixed $value <p>
     * The value to set.
     * </p>
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $classifiedOffset = str_replace(" ", "", ucwords(strtr($offset, "_-", "  ")));
        $method = 'set' . $classifiedOffset;
        if(method_exists($this, $method)){
            $this->$method($value);
        }
        $this->data[$offset] = $value;
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to unset
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset <p>
     * The offset to unset.
     * </p>
     * @return void
     */
    public function offsetUnset($offset)
    {
        $classifiedOffset = str_replace(" ", "", ucwords(strtr($offset, "_-", "  ")));
        $method = 'set' . $classifiedOffset;
        if(method_exists($this, $method)){
            $this->$method(null);
        }

        if(isset($this->data[$offset])){
            unset($this->data[$offset]);
        }
    }

    /**
     * @return array
     */
    public function toArray(){
        return $this->data;
    }

    /**
     * (PHP 5 &gt;= 5.4.0)<br/>
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * @deprecated
     * @throws \Exception
     */
    public function updateCache(){
        throw new \Exception('CacheCollectionElement::updateCache() is deprecated');
    }

    /**
     * @return \Weasty\Doctrine\Cache\Collection\CacheCollection
     * @throws \Weasty\Doctrine\Cache\Collection\Exception\CacheCollectionException
     */
    protected function getCollection(){
        if(!$this->collection){
            $this->collection = $this->getCollectionManager()->getCollection($this->getEntityClassName());
        }
        return $this->collection;
    }

    /**
     * @return CacheCollectionManager
     */
    protected function getCollectionManager(){
        return CacheCollectionManager::getInstance();
    }

    /**
     * @return \Weasty\Doctrine\Entity\AbstractRepository
     */
    protected function getRepository(){
        return $this->getCollection()->getRepository($this->getEntityClassName());
    }

    /**
     * @deprecated
     * @throws \Exception
     */
    protected function getDoctrine(){
        throw new \Exception('CacheCollectionElement::getDoctrine() is deprecated');
    }

    /**
     * @deprecated
     * @throws \Exception
     */
    public function translate()
    {
        throw new \Exception('CacheCollectionElement::translate() is deprecated');
    }

    /**
     * @return array
     */
    function __sleep()
    {
        $names = array();
        $vars = get_object_vars($this);
        foreach($vars as $name => $value){
            $names[] = $name;
        }
        return $names;
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed|null
     */
    function __call($name, $arguments)
    {

        $data_key = str_replace('get_', '', strtolower(preg_replace('~(?<=\\w)([A-Z])~', '_$1', $name)));

        if(isset($this->data[$data_key])){

            return $this->data[$data_key];

        } elseif(method_exists($this->getEntity(), $name)){

            return call_user_func_array(array($this->getEntity(), $name), $arguments);

        } else {

            return null;

        }

    }

    /**
     * @return string
     */
    function __toString()
    {
        return $this->__string;
    }

}