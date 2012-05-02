<?php
/**
 * Файл класса CSafeValidator.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Валидатор CSafeValidator помечает связанные атрибуты как безопасные так, что они могут быть присвоены пакетно.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CSafeValidator.php 2799 2011-01-01 19:31:13Z qiang.xue $
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

