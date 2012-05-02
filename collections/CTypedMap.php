<?php
/**
 * Файл класса CTypedMap.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Класс CTypedMap представляет карту, элементы которой имеют определенный тип.
 *
 * CTypedMap расширяет класс {@link CMap} проверяя при добавлении тип элемента.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CTypedMap.php 2999 2011-02-23 20:20:28Z alexander.makarow $
 * @package system.collections
 * @since 1.0
 */
class CTypedMap extends CMap
{
	private $_type;

	/**
	 * Конструктор.
	 * @param string $type тип класса
	 */
	public function __construct($type)
	{
		$this->_type=$type;
	}

	/**
	 * Добавляет элемент в карту.
	 * Метод переопределяет родительскую реализацию, проверяя
	 * тип вставляемого элемента.
	 * @param integer $index определенная позиция вставки
	 * @param mixed $item новый элемент
	 * @return CTypedMap
	 * @throws CException вызывается, если переданное значение позиции вставки выходит за границы,
	 * карта является картой только для чтения или элемент неправильного типа
	 */
	public function add($index,$item)
	{
		if($item instanceof $this->_type)
			parent::add($index,$item);
		else
			throw new CException(Yii::t('yii','CTypedMap<{type}> can only hold objects of {type} class.',
				array('{type}'=>$this->_type)));
		return $this;
	}
}