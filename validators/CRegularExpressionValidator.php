<?php
/**
 * Файл класса CRegularExpressionValidator.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Валидатор CRegularExpressionValidator проверяет атрибут на соответствие определенному {@link pattern регулярному выражению}.
 * Вы можете инвертировать логику валидации при помощи свойства {@link not} (доступно с версии 1.1.5).
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CRegularExpressionValidator.php 3120 2011-03-25 01:50:48Z qiang.xue $
 * @package system.validators
 * @since 1.0
 */
class CRegularExpressionValidator extends CValidator
{
	/**
	 * @var string регулярное выражение, которому должен соответствовать атрибут
	 */
	public $pattern;
	/**
	 * @var boolean может ли быть значение атрибута пустым или равным null. По умолчанию - true,
	 * т.е. пустой атрибут считается валидным
	 */
	public $allowEmpty=true;
	/**
	 * @var boolean инвертировать ли логику проверки. По умолчанию - false. Если установлено в значение true,
	 * то регулярное выражение, определенное свойством {@link pattern} не должно соответствовать проверяемому значению атрибута
	 * @since 1.1.5
	 */
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
		if($this->pattern===null)
			throw new CException(Yii::t('yii','The "pattern" property must be specified with a valid regular expression.'));
		if((!$this->not && !preg_match($this->pattern,$value)) || ($this->not && preg_match($this->pattern,$value)))
		{
			$message=$this->message!==null?$this->message:Yii::t('yii','{attribute} is invalid.');
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
		if($this->pattern===null)
			throw new CException(Yii::t('yii','The "pattern" property must be specified with a valid regular expression.'));

		$message=$this->message!==null ? $this->message : Yii::t('yii','{attribute} is invalid.');
		$message=strtr($message, array(
			'{attribute}'=>$object->getAttributeLabel($attribute),
		));

		$pattern=$this->pattern;
		$pattern=preg_replace('/\\\\x\{?([0-9a-fA-F]+)\}?/', '\u$1', $pattern);
		$delim=substr($pattern, 0, 1);
		$endpos=strrpos($pattern, $delim, 1);
		$flag=substr($pattern, $endpos + 1);
		if ($delim!=='/')
			$pattern='/' . str_replace('/', '\\/', substr($pattern, 1, $endpos - 1)) . '/';
		else
			$pattern = substr($pattern, 0, $endpos + 1);
		if (!empty($flag))
			$pattern .= preg_replace('/[^igm]/', '', $flag);

		return "
if(".($this->allowEmpty ? "$.trim(value)!='' && " : '').($this->not ? '' : '!')."value.match($pattern)) {
	messages.push(".CJSON::encode($message).");
}
";
	}
}

