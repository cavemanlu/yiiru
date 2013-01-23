<?php
/**
 * Файл класса CStackIterator.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright 2008-2013 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Класс CStackIterator реализует итератор для класса {@link CStack}.
 *
 * Позволяет экземпляру класса CStack возвращать новый итератор для обхода элементов стека.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package system.collections
 * @since 1.0
 */
class CStackIterator implements Iterator
{
	/**
	 * @var array данные, подлежащие итерации
	 */
	private $_d;
	/**
	 * @var integer индекс текущего элемента
	 */
	private $_i;
	/**
	 * @var integer количество элементов
	 */
	private $_c;

	/**
	 * Конструктор.
	 * @param array $data данные, подлежащие итерации
	 */
	public function __construct(&$data)
	{
		$this->_d=&$data;
		$this->_i=0;
		$this->_c=count($this->_d);
	}

	/**
	 * Перемещает указатель на начало.
	 * Метод требуется интерфейсом Iterator.
	 */
	public function rewind()
	{
		$this->_i=0;
	}

	/**
	 * Возвращает ключ текущего элемента массива.
	 * Метод требуется интерфейсом Iterator.
	 * @return integer ключ текущего элемента массива
	 */
	public function key()
	{
		return $this->_i;
	}

	/**
	 * Возвращает текущий элемент массива.
	 * Метод требуется интерфейсом Iterator.
	 * @return mixed текущий элемент массива
	 */
	public function current()
	{
		return $this->_d[$this->_i];
	}

	/**
	 * Перемещает указатель на следующий элемент массива.
	 * Метод требуется интерфейсом Iterator.
	 */
	public function next()
	{
		$this->_i++;
	}

	/**
	 * Показывает, есть ли элемент в данной позиции.
	 * Метод требуется интерфейсом Iterator.
	 * @return boolean
	 */
	public function valid()
	{
		return $this->_i<$this->_c;
	}
}
