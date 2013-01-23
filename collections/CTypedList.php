<?php
/**
 * Файл содержит класс CTypedList.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright 2008-2013 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Экземпляр класса CTypedList представляет собой список элементов одного типа.
 *
 * Класс CTypedList является потомком класса {@link CList} и добавляет проверку
 * типа для добавляемых элементов, чтобы они соответствовали выбранному типу списка.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package system.collections
 * @since 1.0
 */
class CTypedList extends CList
{
	private $_type;

	/**
	 * Конструктор.
	 * @param string $type имя класса
	 */
	public function __construct($type)
	{
		$this->_type=$type;
	}

	/**
	 * Вставляет элемент в выбранную позицию.
	 * Метод переопределяет метод предка, добавляя проверку типа вставляемого
	 * элемента на соответствие типу, переданному при создании списка.
	 * @param integer $index позиция
	 * @param mixed $item элемент
	 * @throws CException Вызывается, если индекс превышает границу, список
	 * только для чтения или элемент не соответствует ожидаемому типу.
	 */
	public function insertAt($index,$item)
	{
		if($item instanceof $this->_type)
			parent::insertAt($index,$item);
		else
			throw new CException(Yii::t('yii','CTypedList<{type}> can only hold objects of {type} class.',
				array('{type}'=>$this->_type)));
	}
}
