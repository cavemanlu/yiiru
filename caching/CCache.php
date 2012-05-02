<?php
/**
 * Файл класса CCache.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Класс CCache - это базовый класс для классов кэша с реализациями хранилищ кэша различного вида.
 *
 * Элемент данных может быть сохранен в кэше вызовом метода {@link set} и позже получен из кэша методом
 * {@link get}. В обеих опрециях требуется ключ, идентифицирующий элемент данных.
 * Также при вызове метода {@link set} могут быть определены срок годности и/или зависимость.
 * Если срок годности элемента данных истек или зависимость изменилась, вызов метода {@link get}
 * не будет возвращать элемент данных.
 *
 * Примечание: по определению, кэш не гарантирует существование значения даже если
 * срок годности не истек. Кэш не является постоянных хранилищем данных.
 *
 * Класс CCache реализует интерфейс {@link ICache} со следующими методами:
 * <ul>
 * <li>{@link get} : получает значени с ключом (если есть) из кэша</li>
 * <li>{@link set} : сохраняет значение с ключом в кэше</li>
 * <li>{@link add} : сохраняет значение только если кэш не содержит такого ключа</li>
 * <li>{@link delete} : удаляет значение с определенным ключом из кэша</li>
 * <li>{@link flush} : очищает весь кэш</li>
 * </ul>
 *
 * Классы-потомки должны реализовывать следующие методы:
 * <ul>
 * <li>{@link getValue}</li>
 * <li>{@link setValue}</li>
 * <li>{@link addValue}</li>
 * <li>{@link deleteValue}</li>
 * <li>{@link flush} (опционально)</li>
 * </ul>
 *
 * Класс CCache также реализует интерфейс ArrayAccess, и поэтому может использоваться как массив.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CCache.php 3515 2011-12-28 12:29:24Z mdomba $
 * @package system.caching
 * @since 1.0
 */
abstract class CCache extends CApplicationComponent implements ICache, ArrayAccess
{
	/**
	 * @var string строка, представляющая собой префикс ключа для уникальности ключей. По умолчанию -
	 * {@link CApplication::getId() идентификатор приложения}
	 */
	public $keyPrefix;

	/**
	 * Инициализирует компонент приложения.
	 * Метод переопределяет родительскую реализацию установкой префикса ключа.
	 */
	public function init()
	{
		parent::init();
		if($this->keyPrefix===null)
			$this->keyPrefix=Yii::app()->getId();
	}

	/**
	 * @param string $key ключ, идентифицирующий кэшируемое значение
	 * @return sring уникальный ключ, сгенерированный из переданного ключа с префиксом
	 */
	protected function generateUniqueKey($key)
	{
		return md5($this->keyPrefix.$key);
	}

	/**
	 * Получает значение из кэша по определенному ключу
	 * @param string $id ключ, идентифицирующий значение кэша
	 * @return mixed значение, сохраненное в кэше; false, если значения нет в кэше, срок годности истек или зависимость изменена
	 */
	public function get($id)
	{
		if(($value=$this->getValue($this->generateUniqueKey($id)))!==false)
		{
			$data=unserialize($value);
			if(!is_array($data))
				return false;
			if(!($data[1] instanceof ICacheDependency) || !$data[1]->getHasChanged())
			{
				Yii::trace('Serving "'.$id.'" from cache','system.caching.'.get_class($this));
				return $data[0];
			}
		}
		return false;
	}

	/**
	 * Получает несколько значений из кэша по определенным ключам.
	 * Некоторые кэши (такие как memcache, apc) позволяют одновременно получать несколько значений из кэша,
	 * что может увеличить производительность из-за снижения количества соединений.
	 * Если кэш не поддерживает данную функцию, данный метод симулирует её.
	 * @param array $ids список ключей, идентифицирующих кэшированные значения
	 * @return array список кэшированных значений, соответствующих переданным ключам.
	 * Возвращается массив пар (ключ, значение). Если значения нет в кэше или его срок
	 * годности истек, соответствующее значение массива будет равно значению false
	 */
	public function mget($ids)
	{
		$uniqueIDs=array();
		$results=array();
		foreach($ids as $id)
		{
			$uniqueIDs[$id]=$this->generateUniqueKey($id);
			$results[$id]=false;
		}
		$values=$this->getValues($uniqueIDs);
		foreach($uniqueIDs as $id=>$uniqueID)
		{
			if(!isset($values[$uniqueID]))
				continue;
			$data=unserialize($values[$uniqueID]);
			if(is_array($data) && (!($data[1] instanceof ICacheDependency) || !$data[1]->getHasChanged()))
			{
				Yii::trace('Serving "'.$id.'" from cache','system.caching.'.get_class($this));
				$results[$id]=$data[0];
			}
		}
		return $results;
	}

	/**
	 * Сохраняет значение, идентифицируемое по ключу, в кэше.
	 * Если кэш уже содержит такой ключ, существующее значение и срок годности будут заменены на новые.
	 *
	 * @param string $id ключ, идентифицирующий кэшируемое значение
	 * @param mixed $value кэшируемое значение
	 * @param integer $expire количество секунд, через которое истечет срок годности кэшируемого значения. 0 означает бесконечный срок годности
	 * @param ICacheDependency $dependency зависимость кэшируемого элемента. Если зависимость изменяется, элемент помечается как недействительный
	 * @return boolean true, если значение успешно сохранено в кэше, иначе - false
	 */
	public function set($id,$value,$expire=0,$dependency=null)
	{
		Yii::trace('Saving "'.$id.'" to cache','system.caching.'.get_class($this));
		if($dependency!==null)
			$dependency->evaluateDependency();
		$data=array($value,$dependency);
		return $this->setValue($this->generateUniqueKey($id),serialize($data),$expire);
	}

	/**
	 * Сохраняет в кэш значение, идентифицируемое ключом, если кэш не содержит данный ключ.
	 * Если такой ключ уже содержится в кэше, ничего не будет выполнено.
	 * @param string $id ключ, идентифицирующий кэшируемое значение
	 * @param mixed $value кэшируемое значение
	 * @param integer $expire количество секунд, через которое истечет срок годности кэшируемого значения. 0 означает бесконечный срок годности
	 * @param ICacheDependency $dependency зависимость кэшируемого элемента. Если зависимость изменяется, элемент помечается как недействительный
	 * @return boolean true, если значение успешно сохранено в кэше, иначе - false
	 */
	public function add($id,$value,$expire=0,$dependency=null)
	{
		Yii::trace('Adding "'.$id.'" to cache','system.caching.'.get_class($this));
		if($dependency!==null)
			$dependency->evaluateDependency();
		$data=array($value,$dependency);
		return $this->addValue($this->generateUniqueKey($id),serialize($data),$expire);
	}

	/**
	 * Удаляет из кэша значение по определенному ключу.
	 * @param string $id ключ удаляемого значения
	 * @return boolean не было ли ошибок при удалении; true - успешное удаление
	 */
	public function delete($id)
	{
		Yii::trace('Deleting "'.$id.'" from cache','system.caching.'.get_class($this));
		return $this->deleteValue($this->generateUniqueKey($id));
	}

	/**
	 * Удаляет все значения из кэша.
	 * Будьте осторожны при выполнении данной операции, если кэш доступен в нескольких приложениях.
	 * @return boolean whether the flush operation was successful.
	 */
	public function flush()
	{
		Yii::trace('Flushing cache','system.caching.'.get_class($this));
		return $this->flushValues();
	}

	/**
	 * Получает значение из кэша по определенному ключу.
	 * Метод должен переопределяться в классах-потомках для получения данных из конкретного кэш-хранилища.
	 * Уникальность и зависимость уже обработаны в методе {@link get()}. Поэтому
	 * необходима только реализация получения данных.
	 * @param string $key уникальный ключ, идентифицирующий кэшированное значение
	 * @return string хранимое в кэше значение; false, если значения в кэше нет или его срок годности истек
	 * @throws CException if this method is not overridden by child classes
	 */
	protected function getValue($key)
	{
		throw new CException(Yii::t('yii','{className} does not support get() functionality.',
			array('{className}'=>get_class($this))));
	}

	/**
	 * Получает из кэша несколько значений с определенными ключами.
	 * Реализация по умолчанию просто вызывает несколько раз метод {@link getValue}
	 * для получения кэшированных значений одно за другим.
	 * Если основное кэш-хранилище поддерживает мультизапрос кэшированных значений, метод
	 * должен быть переопределен, чтобы воспользоваться данной функцией.
	 * @param array $keys список ключей, идентифицирующих кэшированные значения
	 * @return array список кэшированных значений, индексированный по ключам
	 */
	protected function getValues($keys)
	{
		$results=array();
		foreach($keys as $key)
			$results[$key]=$this->getValue($key);
		return $results;
	}

	/**
	 * Сохраняет в кэше значение, идентифицируемое ключом.
	 * Метод должен реализовываться классами-потомками для сохранения данных в конкретном кэш-хранилище.
	 * Уникальность и зависимость уже обработаны в методе {@link get()}. Поэтому
	 * необходима только реализация сохранения данных.
	 * @param string $key ключ, идентифицирующий кэшируемое значение
	 * @param string $value кэшируемое значение
	 * @param integer $expire количество секунд срока годности кэшируемого значения. 0 - без срока годности
	 * @return boolean true, если значение успешно сохранено в кэше, иначе false
	 * @throws CException вызывается, если данный метод не переопределен классами-потомками
	 */
	protected function setValue($key,$value,$expire)
	{
		throw new CException(Yii::t('yii','{className} does not support set() functionality.',
			array('{className}'=>get_class($this))));
	}

	/**
	 * Сохраняет в кэше значение, идентифицируемое ключом, если кэш не содержит данный ключ.
	 * Метод должен реализовываться классами-потомками для сохранения данных в конкретном кэш-хранилище.
	 * Уникальность и зависимость уже обработаны в методе {@link get()}. Поэтому
	 * необходима только реализация сохранения данных.
	 * @param string $key ключ, идентифицирующий кэшируемое значение
	 * @param string $value кэшируемое значение
	 * @param integer $expire количество секунд срока годности кэшируемого значения. 0 - без срока годности
	 * @return boolean true, если значение успешно сохранено в кэше, иначе false
	 * @throws CException вызывается, если данный метод не переопределен классами-потомками
	 */
	protected function addValue($key,$value,$expire)
	{
		throw new CException(Yii::t('yii','{className} does not support add() functionality.',
			array('{className}'=>get_class($this))));
	}

	/**
	 * Удаляет из кеша значение по определенному ключу.
	 * Метод должен реализовываться классами-потомками для удаления данных из конкретного кэш-хранилища.
	 * @param string $key ключ удаляемого значения
	 * @return boolean true, если в процессе удаления не произошло ошибок
	 * @throws CException вызывается, если данный метод не переопределен классами-потомками
	 */
	protected function deleteValue($key)
	{
		throw new CException(Yii::t('yii','{className} does not support delete() functionality.',
			array('{className}'=>get_class($this))));
	}

	/**
	 * Удаляет все значения из кэша.
	 * Классы-потомки могут расширять данный метод для реализации операции очистки
	 * @return boolean успешно ли выполнилась операция очистки
	 * @throws CException вызывается, если данный метод не переопределен классами-потомками
	 * @since 1.1.5
	 */
	protected function flushValues()
	{
		throw new CException(Yii::t('yii','{className} does not support flushValues() functionality.',
			array('{className}'=>get_class($this))));
	}

	/**
	 * Существует ли запись в кэше с заданным ключом.
	 * Метод требуется интерфейсом ArrayAccess.
	 * @param string $id ключ, идентифицирующий кэшированное значение
	 * @return boolean
	 */
	public function offsetExists($id)
	{
		return $this->get($id)!==false;
	}

	/**
	 * Получает значение из кэша по определенному ключу.
	 * Метод требуется интерфейсом ArrayAccess.
	 * @param string $id ключ, идентифицирующий кэшированное значение
	 * @return mixed кэшированное значение; false, если значения в кэше нет или его срок годности истек
	 */
	public function offsetGet($id)
	{
		return $this->get($id);
	}

	/**
	 * Сохраняет в кэше значение, идентифицируемое ключом.
	 * Если кэш уже содержит значение с таким ключом, существующее значение будет
	 * заменено новым. Для добавления срока годности и зависимостей, используйте метод set().
	 * Метод требуется интерфейсом ArrayAccess.
	 * @param string $id ключ, идентифицирующий кэшируемое значение
	 * @param mixed $value кэшируемое значение
	 */
	public function offsetSet($id, $value)
	{
		$this->set($id, $value);
	}

	/**
	 * Удаляет из кеша значение по определенному ключу.
	 * Метод требуется интерфейсом ArrayAccess.
	 * @param string $id ключ, идентифицирующий удаляемое значение
	 * @return boolean true, если в процессе удаления не произошло ошибок
	 */
	public function offsetUnset($id)
	{
		$this->delete($id);
	}
}
