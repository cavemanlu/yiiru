<?php
/**
 * Файл содержит класс, реализующий функции очереди.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Класс CQueue реализует очередь.
 *
 * Типичные операции очереди, реализованные данным классом - {@link enqueue()}, {@link dequeue()} и {@link peek()}.
 * Кроме того, метод {@link contains()} может быть использован для проверки нахождения элемента в очереди.
 * Для получения количества элементов в очереди, используйте свойство {@link getCount count}.
 *
 * Элементы очереди можно обойти, используя foreach как показано далее:
 * <pre>
 * foreach($queue as $item) ...
 * </pre>
 *
 * @property Iterator $iterator итератор для обхода элементов очереди
 * @property integer $count число элементов в очереди
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CQueue.php 3427 2011-10-25 00:03:52Z alexander.makarow $
 * @package system.collections
 * @since 1.0
 */
class CQueue extends CComponent implements IteratorAggregate,Countable
{
	/**
	 * внутреннее хранилище данных
	 * @var array
	 */
	private $_d=array();
	/**
	 * количество элементов
	 * @var integer
	 */
	private $_c=0;

	/**
	 * Конструктор.
	 * Инициализирует очередь массивом или объектом-итератором
	 * @param array $data начальные данные. По умолчанию равно null, что означает без инициализации
	 * @throws CException вызывается, если данные не null и не являются ни массивом ни итератором
	 */
	public function __construct($data=null)
	{
		if($data!==null)
			$this->copyFrom($data);
	}

	/**
	 * @return array список элементов очереди
	 */
	public function toArray()
	{
		return $this->_d;
	}

	/**
	 * Копирует итерируемые данные в очередь.
	 * Помните, что существующие данные стираются перед копированием
	 * @param mixed $data копируемые данные, должен быть массивом или объектом, класс которого реализует интерфейс Traversable
	 * @throws CException вызывается, если данные не являются ни массивом ни Traversable-объектом
	 */
	public function copyFrom($data)
	{
		if(is_array($data) || ($data instanceof Traversable))
		{
			$this->clear();
			foreach($data as $item)
			{
				$this->_d[]=$item;
				++$this->_c;
			}
		}
		else if($data!==null)
			throw new CException(Yii::t('yii','Queue data must be an array or an object implementing Traversable.'));
	}

	/**
	 * Очищает очередь
	 */
	public function clear()
	{
		$this->_c=0;
		$this->_d=array();
	}

	/**
	 * @param mixed $item элемент
	 * @return boolean есть ли элемент в очереди
	 */
	public function contains($item)
	{
		return array_search($item,$this->_d,true)!==false;
	}

	/**
	 * Возвращает элемент в начале очереди
	 * @return mixed элемент в начале очереди
	 * @throws CException вызывается, если очередь пуста
	 */
	public function peek()
	{
		if($this->_c===0)
			throw new CException(Yii::t('yii','The queue is empty.'));
		else
			return $this->_d[0];
	}

	/**
	 * Удаляет объект из начала очереди и возвращает его
	 * @return mixed элемент в начале очереди
	 * @throws CException вызывается, если очередь пуста
	 */
	public function dequeue()
	{
		if($this->_c===0)
			throw new CException(Yii::t('yii','The queue is empty.'));
		else
		{
			--$this->_c;
			return array_shift($this->_d);
		}
	}

	/**
	 * Добавляет объект в конец очереди
	 * @param mixed $item добавляемый в очередь элемент
	 */
	public function enqueue($item)
	{
		++$this->_c;
		array_push($this->_d,$item);
	}

	/**
	 * Возвращает итератор для обхода элементов очереди.
	 * Требуется интерфейсом IteratorAggregate
	 * @return Iterator итератор для обхода элементов очереди
	 */
	public function getIterator()
	{
		return new CQueueIterator($this->_d);
	}

	/**
	 * Возвращае число элементов в очереди
	 * @return integer число элементов в очереди
	 */
	public function getCount()
	{
		return $this->_c;
	}

	/**
	 * Возвращает число элементов в очереди.
	 * Требуется интерфейсом Countable
	 * @return integer число элементов в очереди
	 */
	public function count()
	{
		return $this->getCount();
	}
}
