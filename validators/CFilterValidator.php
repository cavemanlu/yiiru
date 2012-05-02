<?php
/**
 * Файл класса CFilterValidator.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Валидатор CFilterValidator преобразовывает валидируемые данные, используя фильтр.
 *
 * CFilterValidator на самом деле не валидатор, а процессор данных.
 * Он выполняет определенный метод фильтрации над атрибутом и возвращает
 * результат обратно в атрибут. Метод фильтрации должен иметь следующую структуру:
 * <pre>
 * function foo($value) {...return $newValue; }
 * </pre>
 * Многие функции PHP имеют такую структуру (например, trim).
 *
 * Для определения метода фильтрации присвойте свойству {@link filter} имя функции.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CFilterValidator.php 2799 2011-01-01 19:31:13Z qiang.xue $
 * @package system.validators
 * @since 1.0
 */
class CFilterValidator extends CValidator
{
	/**
	 * @var callback метод фильтрации
	 */
	public $filter;

	/**
	 * Валидирует отдельный атрибут.
	 * При возникновении ошибки к объекту добавляется сообщение об ошибке.
	 * @param CModel $object валидируемый объект данных
	 * @param string $attribute имя валидируемого атрибута
	 */
	protected function validateAttribute($object,$attribute)
	{
		if($this->filter===null || !is_callable($this->filter))
			throw new CException(Yii::t('yii','The "filter" property must be specified with a valid callback.'));
		$object->$attribute=call_user_func_array($this->filter,array($object->$attribute));
	}
}
