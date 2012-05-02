<?php
/**
 * Файл класса CDefaultValueValidator.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Валидатор CDefaultValueValidator присваивает атрибутам определенные значения.
 * Он не проводит валидацию. В основном, он позволяет динамически определить в атрибуте
 * значение по умолчанию.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CDefaultValueValidator.php 3515 2011-12-28 12:29:24Z mdomba $
 * @package system.validators
 */
class CDefaultValueValidator extends CValidator
{
	/**
	 * @var mixed значения по умолчанию, в которые должны быть установлены атрибуты.
	 */
	public $value;
	/**
	 * @var boolean должно ли значение по умолчанию устанавливаться только при нулевом или пустом значении атрибута.
	 * По умолчанию - true. Если установлено в false, атрибут всегда будет связываться со значением по умолчанию,
	 * даже если он уже имеет явно присвоенное значение.
	 */
	public $setOnEmpty=true;

	/**
	 * Валидирует отдельный атрибут.
	 * @param CModel $object валидируемый объект данных
	 * @param string $attribute имя валидируемого атрибута
	 */
	protected function validateAttribute($object,$attribute)
	{
		if(!$this->setOnEmpty)
			$object->$attribute=$this->value;
		else
		{
			$value=$object->$attribute;
			if($value===null || $value==='')
				$object->$attribute=$this->value;
		}
	}
}

