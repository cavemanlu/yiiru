<?php
/**
 * Файл класса CDefaultValueValidator.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright 2008-2013 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Валидатор CDefaultValueValidator присваивает атрибутам определенные значения.
 * It does not do validation but rather allows setting a default value at the
 * same time validation is performed. Usually this happens when calling either
 * <code>$model->validate()</code> or <code>$model->save()</code>.
 * [[[[[Он не проводит валидацию. В основном, он позволяет динамически определить в атрибуте
 * значение по умолчанию.]]]]]
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
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

