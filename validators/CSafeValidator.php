<?php
/**
 * Файл класса CSafeValidator.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright 2008-2013 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Валидатор CSafeValidator помечает связанные атрибуты как безопасные так, что они могут быть присвоены пакетно.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package system.validators
 * @since 1.1
 */
class CSafeValidator extends CValidator
{
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

