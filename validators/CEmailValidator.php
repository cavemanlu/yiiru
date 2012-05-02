<?php
/**
 * Файл класса CEmailValidator.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Валидатор CEmailValidator проверяет, что значение атрибута - правильный адрес email.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CEmailValidator.php 3515 2011-12-28 12:29:24Z mdomba $
 * @package system.validators
 * @since 1.0
 */
class CEmailValidator extends CValidator
{
	/**
	 * @var string регулярное выражение, используемое для проверки значения атрибута.
	 * @see http://www.regular-expressions.info/email.html
	 */
	public $pattern='/^[a-zA-Z0-9!#$%&\'*+\\/=?^_`{|}~-]+(?:\.[a-zA-Z0-9!#$%&\'*+\\/=?^_`{|}~-]+)*@(?:[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?\.)+[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?$/';
	/**
	 * @var string регулярное выражение, используемое для проверки адресов email с именем.
	 * Свойство используется только если свойство {@link allowName} установлено в true
	 * @see allowName
	 */
	public $fullPattern='/^[^@]*<[a-zA-Z0-9!#$%&\'*+\\/=?^_`{|}~-]+(?:\.[a-zA-Z0-9!#$%&\'*+\\/=?^_`{|}~-]+)*@(?:[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?\.)+[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?>$/';
	/**
	 * @var boolean допустимо ли имя в адресе email (например, "Qiang Xue <qiang.xue@gmail.com>"). По умолчанию - false
	 * @see fullPattern
	 */
	public $allowName=false;
	/**
	 * @var boolean проверять ли запись MX для адреса email.
	 * По умолчанию - false. Для включения необходимо убедиться, что функция 'checkdnsrr'
	 * существует в вашей инсталляции PHP.
	 */
	public $checkMX=false;
	/**
	 * @var boolean проверять ли порт 25 для адреса email.
	 * По умолчанию - false
	 */
	public $checkPort=false;
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
		if(!$this->validateValue($value))
		{
			$message=$this->message!==null?$this->message:Yii::t('yii','{attribute} is not a valid email address.');
			$this->addError($object,$attribute,$message);
		}
	}

	/**
	 * Проверяет статичное значение на соответствие email-адресу.
	 * Примечание: данный метод не использует свойство {@link allowEmpty}.
	 * Метод предоставлен для того, чтобы можно было вызывать его непосредственно без прохождения механизма правил валидации модели.
	 * @param mixed $value валидируемое значение
	 * @return boolean является ли значение верным email-адресом
	 * @since 1.1.1
	 */
	public function validateValue($value)
	{
		// make sure string length is limited to avoid DOS attacks
		$valid=is_string($value) && strlen($value)<=254 && (preg_match($this->pattern,$value) || $this->allowName && preg_match($this->fullPattern,$value));
		if($valid)
			$domain=rtrim(substr($value,strpos($value,'@')+1),'>');
		if($valid && $this->checkMX && function_exists('checkdnsrr'))
			$valid=checkdnsrr($domain,'MX');
		if($valid && $this->checkPort && function_exists('fsockopen'))
			$valid=fsockopen($domain,25)!==false;
		return $valid;
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
		$message=$this->message!==null ? $this->message : Yii::t('yii','{attribute} is not a valid email address.');
		$message=strtr($message, array(
			'{attribute}'=>$object->getAttributeLabel($attribute),
		));

		$condition="!value.match({$this->pattern})";
		if($this->allowName)
			$condition.=" && !value.match({$this->fullPattern})";

		return "
if(".($this->allowEmpty ? "$.trim(value)!='' && " : '').$condition.") {
	messages.push(".CJSON::encode($message).");
}
";
	}
}
