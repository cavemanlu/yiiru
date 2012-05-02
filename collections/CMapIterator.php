<?php
/**
 * Файл класса CMapIterator.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Класс CMapIterator реализует итератор для класса {@link CMap}.
 *
 * Позволяет экземпляру класса CMap возвращать новый итератор для обхода элементов карты.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CMapIterator.php 3186 2011-04-15 22:34:55Z alexander.makarow $
 * @package system.collections
 * @since 1.0
 */
class CMapIterator implements Iterator
{
	/**
	 * @var array данные, подлежащие итерации
	 */
	private $_d;
	/**
	 * @var array список ключей карты
	 */
	private $_keys;
	/**
	 * @var mixed текущий ключ
	 */
	private $_key;

	/**
	 * Конструктор.
	 * @param array $data данные, подлежащие итерации
	 */
	public function __construct(&$data)
	{
		$this->_d=&$data;
		$this->_keys=array_keys($data);
		$this->_key=reset($this->_keys);
	}

	/**
	 * Перемещает указатель на начало.
	 * Метод требуется интерфейсом Iterator.
	 */
	public function rewind()
	{
		$this->_key=reset($this->_keys);
	}

	/**
	 * Возвращает ключ текущего элемента массива.
	 * Метод требуется интерфейсом Iterator.
	 * @return integer ключ текущего элемента массива
	 */
	public function key()
	{
		return $this->_key;
	}

	/**
	 * Возвращает текущий элемент массива.
	 * Метод требуется интерфейсом Iterator.
	 * @return mixed текущий элемент массива
	 */
	public function current()
	{
		return $this->_d[$this->_key];
	}

	/**
	 * Перемещает указатель на следующий элемент массива.
	 * Метод требуется интерфейсом Iterator.
	 */
	public function next()
	{
		$this->_key=next($this->_keys);
	}

	/**
	 * Показывает, есть ли элемент в данной позиции.
	 * Метод требуется интерфейсом Iterator.
	 * @return boolean
	 */
	public function valid()
	{
		return $this->_key!==false;
	}
}
