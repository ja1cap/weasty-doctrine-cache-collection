<?php
namespace Weasty\Doctrine\Cache\Collection;

use Closure;
use Traversable;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Query;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Cache\Cache;
use Weasty\Doctrine\Entity\AbstractEntity;
use Weasty\Doctrine\Cache\Collection\Exception\CacheCollectionException;
use Weasty\Doctrine\EntitySerializer;

/**
 * Class CacheCollection
 * @package Weasty\Doctrine\Cache\Collection
 */
class CacheCollection implements Collection {

    /**
     * Cache prefix of all collections instances
     */
    const COLLECTION_CACHE_PREFIX = '_ENTITY_CACHE_COLLECTION_';

    /**
     * An array containing the entries of this collection.
     *
     * @var array
     */
    protected $elements;

    /**
     * @var \Doctrine\Common\Cache\Cache
     */
    protected $cache;

    /**
     * @var \Weasty\Doctrine\EntitySerializer
     */
    protected $entitySerializer;

    /**
     * Unique identifier field name of element
     * @var string
     */
    protected $entityIdentifierField;

    /**
     * Entity class name
     * @var string
     */
    protected $entityClassName;

    /**
     * Elements cache id - must be unique for every collection
     * @var string
     */
    protected $elementsCacheId;

    /**
     * Elements amount cache id - must be unique for every collection
     * @var string
     */
    protected $elementsAmountCacheId;

    /**
     * Element cache id prefix - must be unique for every collection
     * @var string
     */
    protected $elementCacheIdPrefix;

    /**
     * Cache id of elements keys that exist in collection
     * @var string
     */
    protected $validKeysCacheId;

    /**
     * @var int
     */
    protected $cacheLifeTime = 0;

    /**
     * Cache id of elements keys that don't exist in collection
     * @var string
     */
    protected $invalidKeysCacheId;

    /**
     * @var \Doctrine\Common\Persistence\ObjectManager
     */
    protected $entityManager;

    function __construct(ObjectManager $entityManager, EntitySerializer $entitySerializer, Cache $cache, $entityClassName, $cacheLifeTime = 0)
    {

        $this->entityManager = $entityManager;
        $this->entitySerializer = $entitySerializer;

        $this->entityClassName = $entityClassName;

        $metadata = $this->entityManager->getClassMetadata($entityClassName);
        $this->entityIdentifierField = current($metadata->getIdentifier());

        $this->cacheLifeTime = $cacheLifeTime;

        $baseCachePrefix = self::COLLECTION_CACHE_PREFIX . $entityClassName;

        $this->elementCacheIdPrefix = $baseCachePrefix . '_ELEMENT_';
        $this->elementsCacheId = $baseCachePrefix . '_ELEMENTS';
        $this->elementsAmountCacheId = $baseCachePrefix . '_AMOUNT';
        $this->validKeysCacheId = $baseCachePrefix . '_VALID_KEYS';
        $this->invalidKeysCacheId = $baseCachePrefix . '_INVALID_KEYS';

    }

    /**
     * @return string
     */
    public function getEntityClassName()
    {
        return $this->entityClassName;
    }

    /**
     * @return string
     */
    public function getEntityIdentifierField()
    {
        return $this->entityIdentifierField;
    }

    /**
     * @param $element
     * @throws \Weasty\Doctrine\Cache\Collection\Exception\CacheCollectionException
     */
    protected function validateElement(&$element){

        if(
            !$element instanceof CacheCollectionElementInterface &&
            !$element instanceof CacheCollectionEntityInterface
        ){
            throw CacheCollectionException::invalidElement();
        }

    }

    /**
     * @param $key
     * @return string
     */
    protected function processKey($key){
        return (string)$key;
    }

    /**
     * @param $element
     * @return string
     */
    protected function getElementKey($element){

        if($element instanceof CacheCollectionElementInterface){

            $key = $element->getIdentifier();

        } else if($element instanceof CacheCollectionEntityInterface){

            $key = $element[$this->entityIdentifierField];

        } else {

            $key = (string)$element;

        }

        return $key;

    }

    /**
     * @param $key
     * @return string
     */
    protected function getElementCacheId($key){

        if(!is_string($key) && !is_numeric($key)){
            $key = $this->getElementKey($key);
        }

        return $this->elementCacheIdPrefix . $key;

    }

    /**
     * @param $key
     * @return $this
     */
    protected function addValidKey($key){
        $keys = $this->getValidKeys();
        if(!in_array($key, $keys)){
            $keys[] = $key;
            $this->setValidKeys($keys);
        }
        return $this;
    }

    /**
     * @param $key
     * @return $this
     */
    protected function removeValidKey($key){
        $keys = $this->getValidKeys();
        $index = array_search($key, $keys);
        if($index !== false){
            unset($keys[$key]);
            $this->setValidKeys($keys);
        }
        return $this;
    }

    /**
     * @return array|mixed
     */
    protected function getValidKeys(){
        $keys = $this->getCache()->fetch($this->validKeysCacheId);
        if(!$keys){
            $keys = array();
        }
        return $keys;
    }

    /**
     * @param array $keys
     * @return $this
     */
    protected function setValidKeys(array $keys = array()){
        $this->getCache()->save($this->validKeysCacheId, $keys, $this->cacheLifeTime);
        return $this;
    }

    /**
     * @param $key
     * @return $this
     */
    protected function addInValidKey($key){
        $keys = $this->getInValidKeys();
        if(!in_array($key, $keys)){
            $keys[] = $key;
            $this->setInValidKeys($keys);
        }
        return $this;
    }

    /**
     * @param $key
     * @return $this
     */
    protected function removeInValidKey($key){
        $keys = $this->getInValidKeys();
        $index = array_search($key, $keys);
        if($index !== false){
            unset($keys[$key]);
            $this->setInValidKeys($keys);
        }
        return $this;
    }

    /**
     * @return array|mixed
     */
    protected function getInValidKeys(){
        $keys = $this->getCache()->fetch($this->invalidKeysCacheId);
        if(!$keys){
            $keys = array();
        }
        return $keys;
    }

    /**
     * @param array $keys
     * @return $this
     */
    protected function setInValidKeys(array $keys = array()){
        $this->getCache()->save($this->invalidKeysCacheId, $keys, $this->cacheLifeTime);
        return $this;
    }

    /**
     * @param $key
     * @return bool
     */
    public function isValidKey($key){

        if(in_array($key, $this->getInValidKeys())){
            return false;
        }

        return true;

    }

    /**
     * @param $element
     * @return CacheCollectionElementInterface
     * @throws \Weasty\Doctrine\Cache\Collection\Exception\CacheCollectionException
     */
    public function saveElement($element){

        $key = $this->getElementKey($element);
        $cacheId = $this->getElementCacheId($key);

        if($element instanceof CacheCollectionEntityInterface){

            $element = $element->createCollectionElement($this);
            $element->setKey($key);
            $element->setCacheId($cacheId);
            $element->setEntityClassName($this->entityClassName);
            $element->setEntityIdentifierField($this->entityIdentifierField);

        }

        if(!$element instanceof CacheCollectionElementInterface){
            throw CacheCollectionException::invalidEntityLazyElement();
        }

        if(!$this->getCache()->contains($cacheId)){
            $this->addValidKey($key);
            $this->removeInValidKey($key);
        }

        $this->elements[$key] = $element;
        $this->getCache()->save($cacheId, $element, $this->cacheLifeTime);

        return $element;

    }

    /**
     * @param $element
     * @return null|CacheCollectionElementInterface|CacheCollectionEntityInterface
     */
    public function deleteElement($element){

        if(is_string($element)){
            $element = $this->fetchElement($element);
        }

        if($element){

            $key = $this->getElementKey($element);
            $this->removeValidKey($key);
            $this->addInValidKey($key);

            $this->getCache()->delete($this->getElementCacheId($element));

            return $element;

        }

        return null;

    }

    /**
     * @param $key
     * @return null|CacheCollectionElementInterface
     */
    public function fetchElement($key){

        $key = $this->processKey($key);
        $element = $this->getCache()->fetch($this->getElementCacheId($key));
        if(!$element instanceof CacheCollectionElementInterface){
            return null;
        }
        return $element;

    }

    /**
     * @param $key
     * @param $entity_identifier_field
     * @return null|AbstractEntity
     */
    public function findElement($key, $entity_identifier_field = null){
        $key = $this->processKey($key);
        return $this->getRepository()->findOneBy(array(
            ($entity_identifier_field ? (string)$entity_identifier_field : $this->entityIdentifierField) => $key
        ));
    }

    /**
     * @param $key
     * @param CacheCollectionEntityInterface $entity
     * @param $entity_identifier_field
     * @return CacheCollectionElementInterface|null
     */
    public function getElement($key, CacheCollectionEntityInterface $entity = null, $entity_identifier_field = null){

        if(!$key){
            return null;
        }

        //TODO create storage of identifier equivalents if $entityIdentifierField no equal to entity primary identifier
        $key = $this->processKey($key);

        //Check elements property
        if(isset($this->elements[$key])){

            $element = $this->elements[$key];

        } else {

            //Fetch element from cache
            $element = $this->fetchElement($key);

            if($element){

                //Add element to the elements property
                $this->elements[$key] = $element;

            } else {

                if(!$entity){

                    //Find element entity in repository
                    $entity = $this->findElement($key, $entity_identifier_field);

                }

                if(!$entity){

                    //Add key to invalid list if element not found if repository
                    $this->addInValidKey($key);

                } else {

                    //Save entity element in collection
                    $element = $this->saveElement($entity);

                }

            }

        }

        return $element;

    }

    /**
     * @return array
     */
    public function getElements(){

        if(!$this->elements){

            if(!$this->elements = $this->getCache()->fetch($this->elementsCacheId)){

                $entities = $this->getRepository()->findAll();
                $elements = array();

                foreach($entities as $entity){

                    if($entity instanceof CacheCollectionEntityInterface){

                        $key = $entity->getIdentifier();

                        if(!$element = $this->fetchElement($key)){
                            $element = $this->saveElement($entity);
                        }

                        $elements[$key] = $element;

                    }

                }

                $this->elements = $elements;
                $this->getCache()->save($this->elementsCacheId, $this->elements, $this->cacheLifeTime);

            }

        }

        return $this->elements;

    }

    /**
     * @return int
     */
    public function getAmount(){

        $amount = $this->getCache()->fetch($this->elementsAmountCacheId);

        if($amount === false){

            $amount = $this->getRepository()->getAmount();

            $this->getCache()->save($this->elementsAmountCacheId, $amount, $this->cacheLifeTime);

        }

        return $amount;

    }

    /**
     * @param int $n
     * @return int
     */
    public function incrementAmount($n = 1){
        $amount = $this->getAmount();
        $amount += $n;
        $this->setAmount($amount);
        return $amount;
    }

    /**
     * @param int $n
     * @return int
     */
    public function decrementAmount($n = 1){
        $amount = $this->getAmount();
        $amount -= $n;
        $this->setAmount($amount);
        return $amount;
    }

    /**
     * @param $amount
     * @return $this
     */
    public function setAmount($amount){
        $this->getCache()->save($this->elementsAmountCacheId, (int)$amount, $this->cacheLifeTime);
        return $this;
    }

    /**
     * Adds an element at the end of the collection.
     *
     * @param mixed $element The element to add.
     *
     * @return boolean Always TRUE.
     * @throws \Weasty\Doctrine\Cache\Collection\Exception\CacheCollectionException
     */
    function add($element)
    {

        //Check if element if valid
        $this->validateElement($element);

        //Cache element
        $this->saveElement($element);

        return true;

    }

    /**
     * Clears the collection, removing all elements.
     *
     * @deprecated CAN EMPTY REPOSITORY
     * @return void
     */
    function clear()
    {
        //$this->getRepository()->clear();
    }

    /**
     * Checks whether an element is contained in the collection.
     * This is an O(n) operation, where n is the size of the collection.
     *
     * @param mixed $element The element to search for.
     *
     * @return boolean TRUE if the collection contains the element, FALSE otherwise.
     */
    function contains($element)
    {

        //Check if element is valid
        $this->validateElement($element);

        //Check elements property
        $contains = in_array($element, $this->elements, true);

        if(!$contains){

            $key = $this->getElementKey($element);

            //If key if valid element exists at list in repository
            $contains = $this->isValidKey($key);

        }

        return $contains;

    }

    /**
     * Checks whether the collection is empty (contains no elements).
     *
     * @return boolean TRUE if the collection is empty, FALSE otherwise.
     */
    function isEmpty()
    {

        if($this->elements){

            $isEmpty = false;

        } else {

            $amount = $this->count();

            $isEmpty = (!$amount || $amount <= 0);

        }

        return $isEmpty;

    }

    /**
     * Removes the element at the specified index from the collection.
     *
     * @param string|integer $key The kex/index of the element to remove.
     *
     * @return mixed The removed element or NULL, if the collection did not contain the element.
     */
    function remove($key)
    {
        return $this->deleteElement($key);
    }

    /**
     * Removes the specified element from the collection, if it is found.
     *
     * @param mixed $element The element to remove.
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    function removeElement($element)
    {
        return $this->deleteElement($element);
    }

    /**
     * Checks whether the collection contains an element with the specified key/index.
     *
     * @param string|integer $key The key/index to check for.
     *
     * @return boolean TRUE if the collection contains an element with the specified key/index,
     *                 FALSE otherwise.
     */
    function containsKey($key)
    {
        return (bool)$this->get($key);
    }

    /**
     * Gets the element at the specified key/index.
     *
     * @param string|integer $key The key/index of the element to retrieve.
     *
     * @return null|CacheCollectionElementInterface|mixed
     */
    function get($key)
    {
        return $this->getElement($key);
    }

    /**
     * Gets all keys/indices of the collection.
     *
     * @return array The keys/indices of the collection, in the order of the corresponding
     *               elements in the collection.
     */
    function getKeys()
    {

        $keys = $this->getValidKeys();
        $amount = $this->count();

        if(count($keys) != $amount){

            $repository = $this->getRepository();
            $expr = $repository->createExpr();

            $expr->setSelect('e.' . $this->entityIdentifierField);

            $keys = $repository->getByExpr($expr, \PDO::FETCH_COLUMN);

            $this->setValidKeys($keys);

        }

        return $keys;

    }

    /**
     * Gets all values of the collection.
     *
     * @return array The values of all elements in the collection, in the order they
     *               appear in the collection.
     */
    function getValues()
    {
        return $this->toArray();
    }

    /**
     * Sets an element in the collection at the specified key/index.
     *
     * @param string|integer $key The key/index of the element to set.
     * @param mixed $value The element to set.
     *
     * @return void
     */
    function set($key, $value)
    {
        $this->saveElement($value);
    }

    /**
     * Gets a native PHP array representation of the collection.
     *
     * @return array
     */
    function toArray()
    {
        return $this->getElements();
    }

    /**
     * Sets the internal iterator to the first element in the collection and returns this element.
     *
     * @return mixed
     */
    function first()
    {
        return $this->getRepository()->findOneBy(array(), array($this->entityIdentifierField => 'ASC'));
    }

    /**
     * Sets the internal iterator to the last element in the collection and returns this element.
     *
     * @return mixed
     */
    function last()
    {
        return $this->getRepository()->findOneBy(array(), array($this->entityIdentifierField => 'DESC'));
    }

    /**
     * Gets the key/index of the element at the current iterator position.
     *
     * @return int|string
     */
    function key()
    {
        return key($this->getElements());
    }

    /**
     * Gets the element of the collection at the current iterator position.
     *
     * @return mixed
     */
    function current()
    {
        return current($this->toArray());
    }

    /**
     * Moves the internal iterator position to the next element and returns this element.
     *
     * @return mixed
     */
    function next()
    {
        return next($this->toArray());
    }

    /**
     * Tests for the existence of an element that satisfies the given predicate.
     *
     * @param Closure $p The predicate.
     *
     * @return boolean TRUE if the predicate is TRUE for at least one element, FALSE otherwise.
     */
    function exists(Closure $p)
    {
        foreach ($this->toArray() as $key => $element) {
            if ($p($key, $element)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns all the elements of this collection that satisfy the predicate p.
     * The order of the elements is preserved.
     *
     * @param Closure $p The predicate used for filtering.
     *
     * @return Collection A collection with the results of the filter operation.
     */
    function filter(Closure $p)
    {
        return new ArrayCollection(array_filter($this->toArray(), $p));
    }

    /**
     * Applies the given predicate p to all elements of this collection,
     * returning true, if the predicate yields true for all elements.
     *
     * @param Closure $p The predicate.
     *
     * @return boolean TRUE, if the predicate yields TRUE for all elements, FALSE otherwise.
     */
    function forAll(Closure $p)
    {
        foreach ($this->toArray() as $key => $element) {
            if ( ! $p($key, $element)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Applies the given function to each element in the collection and returns
     * a new collection with the elements returned by the function.
     *
     * @param Closure $func
     *
     * @return Collection
     */
    function map(Closure $func)
    {
        return new ArrayCollection(array_map($func, $this->toArray()));
    }

    /**
     * Partitions this collection in two collections according to a predicate.
     * Keys are preserved in the resulting collections.
     *
     * @param Closure $p The predicate on which to partition.
     *
     * @return array An array with two elements. The first element contains the collection
     *               of elements where the predicate returned TRUE, the second element
     *               contains the collection of elements where the predicate returned FALSE.
     */
    function partition(Closure $p)
    {
        $coll1 = $coll2 = array();
        foreach ($this->toArray() as $key => $element) {
            if ($p($key, $element)) {
                $coll1[$key] = $element;
            } else {
                $coll2[$key] = $element;
            }
        }
        return array(new ArrayCollection($coll1), new ArrayCollection($coll2));
    }

    /**
     * Gets the index/key of a given element. The comparison of two elements is strict,
     * that means not only the value but also the type must match.
     * For objects this means reference equality.
     *
     * @param mixed $element The element to search for.
     *
     * @return int|string|bool The key/index of the element or FALSE if the element was not found.
     */
    function indexOf($element)
    {
        $this->validateElement($element);
        $key = $this->getElementKey($element);

        if(isset($this->elements[$key])){
            return $this->elements[$key];
        } else {
            return false;
        }
    }

    /**
     * Extracts a slice of $length elements starting at position $offset from the Collection.
     *
     * If $length is null it returns all elements from $offset to the end of the Collection.
     * Keys have to be preserved by this method. Calling this method will only return the
     * selected slice and NOT change the elements contained in the collection slice is called on.
     *
     * @param int $offset The offset to start from.
     * @param int|null $length The maximum number of elements to return, or null for no limit.
     *
     * @return array
     */
    function slice($offset, $length = null)
    {
        return array_slice($this->toArray(), $offset, $length, true);
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Retrieve an external iterator
     * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return Traversable An instance of an object implementing <b>Iterator</b> or
     * <b>Traversable</b>
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->toArray());
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
        return $this->containsKey($offset);
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
        return $this->get($offset);
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
        $this->set($offset, $value);
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
        $this->remove($offset);
    }

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Count elements of an object
     * @link http://php.net/manual/en/countable.count.php
     * @return int The custom count as an integer.
     * </p>
     * <p>
     * The return value is cast to an integer.
     */
    public function count()
    {
        return $this->getAmount();
    }

    /**
     * @param \Doctrine\Common\Cache\Cache $cache
     * @return $this
     */
    public function setCache(Cache $cache)
    {
        $this->cache = $cache;
        return $this;
    }

    /**
     * @return \Doctrine\Common\Cache\Cache
     */
    public function getCache()
    {
        if(!$this->cache){
            $this->cache = new ArrayCache();
        }
        return $this->cache;
    }

    /**
     * @return \Doctrine\Common\Persistence\ObjectManager
     */
    public function getEntityManager()
    {
        return $this->entityManager;
    }

    /**
     * @return \Weasty\Doctrine\Entity\AbstractRepository
     */
    public function getRepository(){
        return $this->entityManager->getRepository($this->getEntityClassName());
    }

    /**
     * @return \Weasty\Doctrine\EntitySerializer
     */
    public function getEntitySerializer()
    {
        return $this->entitySerializer;
    }

    /**
     * @deprecated
     * @throws \Exception
     */
    protected function getDoctrine(){
        throw new \Exception('CacheCollection::getDoctrine() is deprecated');
    }

} 