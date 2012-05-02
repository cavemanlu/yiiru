<?php
/**
 * Файл класса CBooleanValidator.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Валидатор CBooleanValidator проверяет значение атрибута на соответствие либо свойству {@link trueValue}
 * либо свойству {@link falseValue}.
 *
 * При использовании свойства {@link message} для определения сообщения об
 * ошибке сообщение может содержать дополнительные метки, которые будут
 * заменены реальным содержимым. В дополнение к метке "{attribute}",
 * распознаваемой всеми валидаторами (см. {@link CValidator}),
 * CBooleanValidator позволяет определять следующие метки:
 * <ul>
 * <li>{true}: заменяется значением, представляющим статус true {@link trueValue}.</li>
 * <li>{false}: заменяется значением, представляющим статус false {@link falseValue}.</li>
 * </ul>
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CBooleanValidator.php 3515 2011-12-28 12:29:24Z mdomba $
 * @package system.validators
 */
class CBooleanValidator extends CValidator
{
	/**
	 * @var mixed значение, представляющее статус true. По умолчанию - '1'.
	 */
	public $trueValue='1';
	/**
	 * @var mixed значение, представляющее статус false. По умолчанию - '0'.
	 */
	public $falseValue='0';
	/**
	 * @var boolean должно ли сравнение с {@link trueValue} и {@link falseValue} быть строгим.
	 * Если да, и тип и значение атрибута должны соответствовать {@link trueValue} или {@link falseValue}.
	 * По умолчанию - false, т.е. проверяются только значения.
	 */
	public $strict=false;
	/**
	 * @var boolean может ли быть значение атрибута пустым или равным null. По умолчанию - true,
	 * т.е. пустой атрибут считается валидным
	 */
	public $allowEmpty=true;

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
		if(!$this->strict && $value!=$this->trueValue && $value!=$this->falseValue
			|| $this->strict && $value!==$this->trueValue && $value!==$this->falseValue)
		{
			$message=$this->message!==null?$this->message:Yii::t('yii','{attribute} must be either {true} or {false}.');
			$this->addError($object,$attribute,$message,array(
				'{true}'=>$this->trueValue,
				'{false}'=>$this->falseValue,
			));
		}
	}

	/**
	 * Возвращает JavaScript-код, требуемый для выполнения валидации на стороне
	 * клиента
	 * @param CModel $object валидируемый объект
	 * @param string $attribute валидируемый атрибут
	 * @return string скрипт валидации на стороне клиента
	 * @see CActiveForm::enableClientValidation
	 * @since 1.1.7
	 */
	public function clientValidateAttribute($object,$attribute)
	{
		$message=$this->message!==null ? $this->message : Yii::t('yii','{attribute} must be either {true} or {false}.');
		$message=strtr($message, array(
			'{attribute}'=>$object->getAttributeLabel($attribute),
			'{true}'=>$this->trueValue,
			'{false}'=>$this->falseValue,
		));
		return "
if(".($this->allowEmpty ? "$.trim(value)!='' && " : '')."value!=".CJSON::encode($this->trueValue)." && value!=".CJSON::encode($this->falseValue).") {
	messages.push(".CJSON::encode($message).");
}
";
	}
}
