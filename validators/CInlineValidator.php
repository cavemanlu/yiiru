<?php
/**
 * Файл класса CInlineValidator.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Валидатор CInlineValidator представляет валидатор, определенный как метод валидируемого объекта.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CInlineValidator.php 3517 2011-12-28 23:22:21Z mdomba $
 * @package system.validators
 * @since 1.0
 */
class CInlineValidator extends CValidator
{
	/**
	 * @var string имя метода валидации в active record-классе
	 */
	public $method;
	/**
	 * @var array дополнительные параметры, передаваемые в метод валидации
	 */
	public $params;
	/**
	 * @var string имя метода, возвращающего скрипт клиентской валидации
	 * (см. {@link clientValidateAttribute})
	 */
	public $clientValidate;

	/**
	 * Валидирует отдельный атрибут.
	 * При возникновении ошибки к объекту добавляется сообщение об ошибке.
	 * @param CModel $object валидируемый объект данных
	 * @param string $attribute имя валидируемого атрибута
	 */
	protected function validateAttribute($object,$attribute)
	{
		$method=$this->method;
		$object->$method($attribute,$this->params);
	}

	/**
	 * Возвращает JavaScript-код, требуемый для выполнения валидации на стороне
	 * клиента, вызовом метода, имя которого задано свойством
	 * {@link clientValidate}. В коде клиентской валидации предопределены
	 * следующие переменные:
	 * <ul>
	 * <li>value: текущее введенное значение атрибута;</li>
	 * <li>messages: массив оишбок, который может быть добавлен к сообщениям об
	 * ошибках атрибута;</li>
	 * <li>attribute: структура данных, хранящая все настройки для валибации
	 * атрибута на стороне клиента.</li>
	 * </ul>
	 * <b>Пример</b>:
	 *
	 * Если свойство {@link clientValidate} установлено в значение
	 * "clientValidate123", то clientValidate123() - это имя метода,
	 * возвращающего код валидации на стороне клиента, и данный метод может
	 * выглядеть так:
	 * <pre>
	 * <?php
	 *   public function clientValidate123($attribute)
	 *   {
	 *      $js = "if(value != '123') { messages.push('Значение должно равняться 123'); }";
	 *      return $js;
	 *   }
	 * ?>
	 * </pre>
	 * @param CModel $object валидируемый объект
	 * @param string $attribute валидируемый атрибут
	 * @return string скрипт валидации на стороне клиента
	 * @see CActiveForm::enableClientValidation
	 * @since 1.1.9
	 */
	public function clientValidateAttribute($object,$attribute)
	{
		if($this->clientValidate!==null)
		{
			$method=$this->clientValidate;
			return $object->$method($attribute);
		}
	}
}
