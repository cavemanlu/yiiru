<?php
/**
 * Файл содержит класс, реулизующий функцию стека.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Класс CStack реализует стек.
 *
 * Типичные реализуемые операции стека - {@link push()}, {@link pop()} и {@link peek()}.
 * Кроме того, метод {@link contains()} может быть использован для проверки наличия элемента в стеке.
 * Для получения количества элементов в стеке используйте свойство {@link getCount Count}.
 *
 * Элементы стека можно обойти, используя foreach как показано далее:
 * <pre>
 * foreach($stack as $item) ...
 * </pre>
 *
 * @property Iterator $iterator итератор для обхода элементов стека
 * @property integer $count число элементов в стеке
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CStack.php 3427 2011-10-25 00:03:52Z alexander.makarow $
 * @package system.collections
 * @since 1.0
 */
class CStack extends CComponent implements IteratorAggregate,Countable
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
	 * Инициализирует стек массивом или объектом-итератором
	 * @param array $data начальные данные. По умолчанию равно null, что означает без инициализации
	 * @throws CException вызывается, если данные не null и не являются ни массивом ни итератором
	 */
	public function __construct($data=null)
	{
		if($data!==null)
			$this->copyFrom($data);
	}

	/**
	 * @return array список элементов стека
	 */
	public function toArray()
	{
		return $this->_d;
	}

	/**
	 * Копирует итерируемые данные в стек.
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
			throw new CException(Yii::t('yii','Stack data must be an array or an object implementing Traversable.'));
	}

	/**
	 * Очищает стек
	 */
	public function clear()
	{
		$this->_c=0;
		$this->_d=array();
	}

	/**
	 * @param mixed $item элемент
	 * @return boolean есть ли элемент в стеке
	 */
	public function contains($item)
	{
		return array_search($item,$this->_d,true)!==false;
	}

	/**
	 * Возвращает верхний элемент стека.
	 * В отличие от метода {@link pop()}, не удаляет элемент из стека
	 * @return mixed верхний элемент стека
	 * @throws CException вызывается, если стек пустой
	 */
	public function peek()
	{
		if($this->_c)
			return $this->_d[$this->_c-1];
		else
			throw new CException(Yii::t('yii','The stack is empty.'));
	}

	/**
	 * Удаляет верхний элемент из стека и возвращает его.
	 * @return mixed верхний элемент стека
	 * @throws CException вызывается, если стек пустой
	 */
	public function pop()
	{
		if($this->_c)
		{
			--$this->_c;
			return array_pop($this->_d);
		}
		else
			throw new CException(Yii::t('yii','The stack is empty.'));
	}

	/**
	 * Вставляет элемент в стек
	 * @param mixed $item добавляемый в стек элемент
	 */
	public function push($item)
	{
		++$this->_c;
		array_push($this->_d,$item);
	}

	/**
	 * Возвращает итератор для обхода элементов стека.
	 * Требуется интерфейсом IteratorAggregate
	 * @return Iterator итератор для обхода элементов стека
	 */
	public function getIterator()
	{
		return new CStackIterator($this->_d);
	}

	/**
	 * Возвращает число элементов в стеке
	 * @return integer число элементов в стеке
	 */
	public function getCount()
	{
		return $this->_c;
	}

	/**
	 * Возвращает число элементов в стеке.
	 * Требуется интерфейсом Countable
	 * @return integer число элементов в стеке
	 */
	public function count()
	{
		return $this->getCount();
	}
}
