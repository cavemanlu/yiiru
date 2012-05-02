<?php
/**
 * Файл содержит класс, реализующий функции списка.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Класс CList реализует коллекцию с целочисленными индексами.
 *
 * Вы можете получать, добавлять, вставлять и удалять элементы, используя методы
 * {@link itemAt}, {@link add}, {@link insertAt}, {@link remove}, и {@link removeAt}.
 * Для получения количества элементов в списке, используйте {@link getCount}.
 * Объект класса CList может также использоваться в качестве обычного массива:
 * <pre>
 * $list[]=$item;  // добавить в конец
 * $list[$index]=$item; // $index должен лежать в диапазоне между 0 и $list->Count
 * unset($list[$index]); // удалить элемент с индексом $index
 * if(isset($list[$index])) // есть ли элемент в данной позиции списка
 * foreach($list as $index=>$item) // обход элементов списка
 * $n=count($list); // количество элементов списка
 * </pre>
 *
 * Для расширения CList до выполнения дополнительных операций добавления и удаления
 * (например, проверка типа), переопределяйте методы {@link insertAt()} и {@link removeAt()}.
 *
 * @property boolean $readOnly только для чтения ли данный список или нет. По
 * умолчанию false
 * @property Iterator $iterator итератор для обхода элементов списка
 * @property integer $count количество элементов списка
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CList.php 3430 2011-11-02 23:10:03Z alexander.makarow@gmail.com $
 * @package system.collections
 * @since 1.0
 */
class CList extends CComponent implements IteratorAggregate,ArrayAccess,Countable
{
	/**
	 * @var array внутреннее хранилище данных
	 */
	private $_d=array();
	/**
	 * @var integer количество элементов
	 */
	private $_c=0;
	/**
	 * @var boolean только для чтения ли данный список
	 */
	private $_r=false;

	/**
	 * Конструктор.
	 * Инициализирует список массивом или итерируемым объектом.
	 * @param array $data начальные данные. По умолчанию null - без инициализации.
	 * @param boolean $readOnly только для чтения ли данный список
	 * @throws CException вызывается, если данные не нулевые и не являются ни массивом ни итератором.
	 */
	public function __construct($data=null,$readOnly=false)
	{
		if($data!==null)
			$this->copyFrom($data);
		$this->setReadOnly($readOnly);
	}

	/**
	 * @return boolean только для чтения ли данный список или нет. По умолчанию false
	 */
	public function getReadOnly()
	{
		return $this->_r;
	}

	/**
	 * @param boolean $value только для чтения ли данный список или нет
	 */
	protected function setReadOnly($value)
	{
		$this->_r=$value;
	}

	/**
	 * Возвращает итератор для обхода элементов списка.
	 * Метод требуется интерфейсом IteratorAggregate
	 * @return CIterator итератор для обхода элементов списка
	 */
	public function getIterator()
	{
		return new CListIterator($this->_d);
	}

	/**
	 * Возвращает количество элементов списка.
	 * Метод требуется интерфейсом Countable
	 * @return integer количество элементов списка
	 */
	public function count()
	{
		return $this->getCount();
	}

	/**
	 * Возвращает количество элементов списка
	 * @return integer количество элементов списка
	 */
	public function getCount()
	{
		return $this->_c;
	}

	/**
	 * Возвращает элемент по определенному смещению.
	 * Метод в точности такой же как метод {@link offsetGet}.
	 * @param integer $index индекс элемента
	 * @return mixed элемент при индексе
	 * @throws CException вызывается, если индекс выходит за границы диапазона
	 */
	public function itemAt($index)
	{
		if(isset($this->_d[$index]))
			return $this->_d[$index];
		else if($index>=0 && $index<$this->_c) // in case the value is null
			return $this->_d[$index];
		else
			throw new CException(Yii::t('yii','List index "{index}" is out of bound.',
				array('{index}'=>$index)));
	}

	/**
	 * Добавляет элемент в конец списка.
	 * @param mixed $item новый элемент
	 * @return integer индекс, в который был добавлен элемент
	 */
	public function add($item)
	{
		$this->insertAt($this->_c,$item);
		return $this->_c-1;
	}

	/**
	 * Вставляет элемент в определенную позицию.
	 * Текущий элемент в данной позиции и последующие будут сдвинуты на один шаг к концу
	 * @param integer $index определенная позиция.
	 * @param mixed $item новый элемент
	 * @throws CException вызывается, если переданный индекс превышает границы списка или список только для чтения
	 */
	public function insertAt($index,$item)
	{
		if(!$this->_r)
		{
			if($index===$this->_c)
				$this->_d[$this->_c++]=$item;
			else if($index>=0 && $index<$this->_c)
			{
				array_splice($this->_d,$index,0,array($item));
				$this->_c++;
			}
			else
				throw new CException(Yii::t('yii','List index "{index}" is out of bound.',
					array('{index}'=>$index)));
		}
		else
			throw new CException(Yii::t('yii','The list is read only.'));
	}

	/**
	 * Удаляет элемент из списка.
	 * Сначала выполняется поиск элемента в списке.
	 * Первый найденный элемент удаляется из списка.
	 * @param mixed $item удаляемый элемент.
	 * @return integer индекс, из которого был удален элемент
	 * @throws CException вызывается, если элемент не существует
	 */
	public function remove($item)
	{
		if(($index=$this->indexOf($item))>=0)
		{
			$this->removeAt($index);
			return $index;
		}
		else
			return false;
	}

	/**
	 * Удаляет элемент из определенной позиции.
	 * @param integer $index индекс удаляемого элемента.
	 * @return mixed удаленный элемент.
	 * @throws CException вызывается, если переданный индекс превышает границы списка или список только для чтения
	 */
	public function removeAt($index)
	{
		if(!$this->_r)
		{
			if($index>=0 && $index<$this->_c)
			{
				$this->_c--;
				if($index===$this->_c)
					return array_pop($this->_d);
				else
				{
					$item=$this->_d[$index];
					array_splice($this->_d,$index,1);
					return $item;
				}
			}
			else
				throw new CException(Yii::t('yii','List index "{index}" is out of bound.',
					array('{index}'=>$index)));
		}
		else
			throw new CException(Yii::t('yii','The list is read only.'));
	}

	/**
	 * Очищает список
	 */
	public function clear()
	{
		for($i=$this->_c-1;$i>=0;--$i)
			$this->removeAt($i);
	}

	/**
	 * @param mixed $item элемент
	 * @return boolean содержит ли список элемент
	 */
	public function contains($item)
	{
		return $this->indexOf($item)>=0;
	}

	/**
	 * @param mixed $item элемент
	 * @return integer индекс элемнта в списке (начало счета с 0); -1, если не найден.
	 */
	public function indexOf($item)
	{
		if(($index=array_search($item,$this->_d,true))!==false)
			return $index;
		else
			return -1;
	}

	/**
	 * @return array список элементов массива
	 */
	public function toArray()
	{
		return $this->_d;
	}

	/**
	 * Копирует итерируемые данные в список.
	 * Примечание: существующие данные сначала будут очищены.
	 * @param mixed $data копируемые данные; должны быть массивом или Traversable-объектом
	 * @throws CException вызывается, если данные не являются ни массивом ни итератором.
	 */
	public function copyFrom($data)
	{
		if(is_array($data) || ($data instanceof Traversable))
		{
			if($this->_c>0)
				$this->clear();
			if($data instanceof CList)
				$data=$data->_d;
			foreach($data as $item)
				$this->add($item);
		}
		else if($data!==null)
			throw new CException(Yii::t('yii','List data must be an array or an object implementing Traversable.'));
	}

	/**
	 * Сливает итерируемые данные в список.
	 * Новые данные будут добавлены в конец существующих данных.
	 * @param mixed $data данные для слияния; должны быть массивом или Traversable-объектом
	 * @throws CException вызывается, если данные не являются ни массивом ни итератором.
	 */
	public function mergeWith($data)
	{
		if(is_array($data) || ($data instanceof Traversable))
		{
			if($data instanceof CList)
				$data=$data->_d;
			foreach($data as $item)
				$this->add($item);
		}
		else if($data!==null)
			throw new CException(Yii::t('yii','List data must be an array or an object implementing Traversable.'));
	}

	/**
	 * Находится ли элемент на данном смещении (ключе).
	 * Метод требуется интерфейсом ArrayAccess.
	 * @param mixed $offset смещение для проверки
	 * @return boolean
	 */
	public function offsetExists($offset)
	{
		return ($offset>=0 && $offset<$this->_c);
	}

	/**
	 * Возвращает элемент на данном смещении (ключе).
	 * Метод требуется интерфейсом ArrayAccess.
	 * @param integer $offset смещение для получения элемента.
	 * @return mixed элемент на данном смещении (ключе)
	 * @throws CException вызывается, если смещения неверно
	 */
	public function offsetGet($offset)
	{
		return $this->itemAt($offset);
	}

	/**
	 * Устанавливает элемент в определенное смещение.
	 * Метод требуется интерфейсом ArrayAccess.
	 * @param integer $offset смещение для установки элемента
	 * @param mixed $item элемент
	 */
	public function offsetSet($offset,$item)
	{
		if($offset===null || $offset===$this->_c)
			$this->insertAt($this->_c,$item);
		else
		{
			$this->removeAt($offset);
			$this->insertAt($offset,$item);
		}
	}

	/**
	 * Удаляет элемент на определенном смещении.
	 * Метод требуется интерфейсом ArrayAccess.
	 * @param mixed $offset смещение для удаления элемента
	 */
	public function offsetUnset($offset)
	{
		$this->removeAt($offset);
	}
}

