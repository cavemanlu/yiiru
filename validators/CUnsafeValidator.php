<?php
/**
 * Файл класса CUnsafeValidator.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Валидатор CUnsafeValidator помечает связанные атрибуты как небезопасные так, что они не могут быть присвоены пакетно.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CUnsafeValidator.php 2799 2011-01-01 19:31:13Z qiang.xue $
 * @package system.validators
 * @since 1.0
 */
class CUnsafeValidator extends CValidator
{
	/**
	 * @var boolean должны ли атрибуты данного валидатора считаться безопасными для пакетного присваивания.
	 * По умолчанию - false.
	 * @since 1.1.4
	 */
	public $safe=false;
	/**
	 * Валидирует отдельный атрибут.
	 * При возникновении ошибки к объекту добавляется сообщение об ошибке.
	 * @param CModel $object валидируемый объект данных
	 * @param string $attribute имя валидируемого атрибута
	 */
	protected function validateAttribute($object,$attribute)
	{
	}
}
