<?php
/**
 * Файл класса CModelEvent.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright 2008-2013 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */


/**
 * Класс CModelEvent.
 *
 * Класс CModelEvent представляет параметры события, необходимые для перехвата события моделью.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package system.base
 * @since 1.0
 */
class CModelEvent extends CEvent
{
	/**
	 * @var boolean является ли модель валидной и должна ли продолжать свой нормальный цикл выполнения.
	 * По умолчанию - true. Например, когда данное событие перехватывается обработчиком события
	 * в объекте {@link CFormModel} при выполнении метода {@link CModel::beforeValidate}, и данное
	 * свойство имеет значение false, метод {@link CModel::validate} завершится после обработки данного события.
	 * Если свойство имеет значение true, будет продолжаться нормальный цикл выполнения, включая
	 * выполнение валидации и вызов метода {@link CModel::afterValidate}.
	 */
	public $isValid=true;
}
