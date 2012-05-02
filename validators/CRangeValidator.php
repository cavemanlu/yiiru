<?php
/**
 * Файл класса CRangeValidator.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Валидатор CRangeValidator проверяет, чтобы значение атрибута было в списке, определенном свойством {@link range}).
 * Вы можете инвертировать логику валидации при помощи свойства {@link not} (доступно с версии 1.1.5).
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CRangeValidator.php 3120 2011-03-25 01:50:48Z qiang.xue $
 * @package system.validators
 * @since 1.0
 */
class CRangeValidator extends CValidator
{
	/**
	 * @var array список допустимых значений, среди которых должен быть атрибут
	 */
	public $range;
	/**
	 * @var boolean должна ли проверка быть строгой (и тип и значение должны соответствовать)
	 */
	public $strict=false;
	/**
	 * @var boolean может ли быть значение атрибута пустым или равным null. По умолчанию - true,
	 * т.е. пустой атрибут считается валидным
	 */
	public $allowEmpty=true;
	/**
	 * @var boolean инвертировать ли логику валидации. По умолчанию - false. Если установлено в значение true,
	 * то значение атрибут не должно находиться в списке значений, определенных свойством {@link range}.
	 * @since 1.1.5
	 **/
 	public $not=false;

	/**
	 * Валидирует отдельный атрибут.
	 * При возникновении ошибки к объекту добавляется сообщение об ошибке.
	 * @param CModel $object валидируемый объект данных
	 * @param string $attribute имя валидируемого атрибута
	 */
	protected function validateAttribute($object,$attribute)
	{
		$value=$object->$attribute;
		if($this->allowEmpty && $this->isEmpty($value))
			return;
		if(!is_array($this->range))
			throw new CException(Yii::t('yii','The "range" property must be specified with a list of values.'));
		if(!$this->not && !in_array($value,$this->range,$this->strict))
		{
			$message=$this->message!==null?$this->message:Yii::t('yii','{attribute} is not in the list.');
			$this->addError($object,$attribute,$message);
		}
		else if($this->not && in_array($value,$this->range,$this->strict))
		{
			$message=$this->message!==null?$this->message:Yii::t('yii','{attribute} is in the list.');
			$this->addError($object,$attribute,$message);
		}
	}

	/**
	 * Returns the JavaScript needed for performing client-side validation.
	 * @param CModel $object the data object being validated
	 * @param string $attribute the name of the attribute to be validated.
	 * @return string the client-side validation script.
	 * @see CActiveForm::enableClientValidation
	 * @since 1.1.7
	 */
	public function clientValidateAttribute($object,$attribute)
	{
		if(!is_array($this->range))
			throw new CException(Yii::t('yii','The "range" property must be specified with a list of values.'));

		if(($message=$this->message)===null)
			$message=$this->not ? Yii::t('yii','{attribute} is in the list.') : Yii::t('yii','{attribute} is not in the list.');
		$message=strtr($message,array(
			'{attribute}'=>$object->getAttributeLabel($attribute),
		));

		$range=array();
		foreach($this->range as $value)
			$range[]=(string)$value;
		$range=CJSON::encode($range);

		return "
if(".($this->allowEmpty ? "$.trim(value)!='' && " : '').($this->not ? "$.inArray(value, $range)>=0" : "$.inArray(value, $range)<0").") {
	messages.push(".CJSON::encode($message).");
}
";
	}
}