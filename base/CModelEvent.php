<?php
/**
 * Файл класса CModelEvent.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */


/**
 * Класс CModelEvent.
 *
 * Класс CModelEvent представляет параметры события, необходимые для перехвата события моделью.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CModelEvent.php 2799 2011-01-01 19:31:13Z qiang.xue $
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
	/**
	 * @var CDbCrireria критерий запроса, передаваемый в качестве параметра в метод find класса {@link CActiveRecord}.
	 * Помните, что данное свойство используется только событием {@link CActiveRecord::onBeforeFind}.
	 * Данное свойство может принимать значение null.
	 * @since 1.1.5
	 */
	public $criteria;
}
