<?php
/**
 * Файл класса CStringValidator.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Валидатор CStringValidator проверяет соответствие длины строкового атрибута некоторой величине.
 *
 * Примечание: валидатор должен использоваться только для строковых атрибутов.
 *
 * В дополнение к свойству {@link message} для установки пользовательского
 * сообщения об ошибке, CStringValidator имеет еще два вида пользовательских
 * сообщений, которые можно установить согласно различным сценариям валидации.
 * Для определения пользовательского сообщения об ошибке о том, что строка
 * слишком короткая, можно использовать свойство {@link tooShort}.
 * Аналогично свойство {@link tooLong} для установки сообщения в случае
 * слишком длинной строки. Эти сообщения содержат дополнительные метки,
 * заменяемые реальным содержимым. В дополнение к метке "{attribute}",
 * распознаваемой всеми валидаторами (see {@link CValidator}), CStringValidator
 * позволяет определять следующие метки:
 * <ul>
 * <li>{min}: при использовании {@link tooShort} заменяется минимальной длиной строки - {@link min} (если она указана);</li>
 * <li>{max}: при использовании {@link tooLong} заменяется максимальной длиной строки - {@link max} (если она указана);</li>
 * <li>{length}: при использовании {@link message} заменяется точно требуемой длиной строки - {@link is}, if set (если она указана).</li>
 * </ul>
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CStringValidator.php 3491 2011-12-17 05:17:57Z jefftulsa $
 * @package system.validators
 * @since 1.0
 */
class CStringValidator extends CValidator
{
	/**
	 * @var integer максимальная длина. По умолчанию - null, т.е. без
	 * ограничения максимума длины
	 */
	public $max;
	/**
	 * @var integer минимальная длина. По умолчанию - null, т.е. без
	 * ограничения минимума длины
	 */
	public $min;
	/**
	 * @var integer точная длина. По умолчанию - null, т.е. без точной длины
	 */
	public $is;
	/**
	 * @var string пользовательское сообщение об ошибке, используемое, если сообщение слишком длинное
	 */
	public $tooShort;
	/**
	 * @var string пользовательское сообщение об ошибке, используемое, если сообщение слишком короткое
	 */
	public $tooLong;
	/**
	 * @var boolean может ли быть значение атрибута пустым или равным null. По умолчанию - true,
	 * т.е. пустой атрибут считается валидным
	 */
	public $allowEmpty=true;
	/**
	 * @var string кодировка строки валидируемого значения (например, 'UTF-8').
	 
	 * Установка данного свойства требует включенного PHP расширения mbstring.
	 * Значение данного свойства будет использовано в качестве второго
	 * параметра функции mb_strlen(). По умолчанию равно кодировке приложения,
	 * т.е., для вычисления длины строки будет использоваться кодировка
	 * приложения, если доступна функция mb_strlen(), иначе используется
	 * функция strlen(). Если данное свойство установлено в значение false, то
	 * функция strlen() будет использоваться даже если включено расширение mbstring
	 * @since 1.1.1
	 */
	public $encoding;

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

		if(function_exists('mb_strlen') && $this->encoding!==false)
			$length=mb_strlen($value, $this->encoding ? $this->encoding : Yii::app()->charset);
		else
			$length=strlen($value);

		if($this->min!==null && $length<$this->min)
		{
			$message=$this->tooShort!==null?$this->tooShort:Yii::t('yii','{attribute} is too short (minimum is {min} characters).');
			$this->addError($object,$attribute,$message,array('{min}'=>$this->min));
		}
		if($this->max!==null && $length>$this->max)
		{
			$message=$this->tooLong!==null?$this->tooLong:Yii::t('yii','{attribute} is too long (maximum is {max} characters).');
			$this->addError($object,$attribute,$message,array('{max}'=>$this->max));
		}
		if($this->is!==null && $length!==$this->is)
		{
			$message=$this->message!==null?$this->message:Yii::t('yii','{attribute} is of the wrong length (should be {length} characters).');
			$this->addError($object,$attribute,$message,array('{length}'=>$this->is));
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
		$label=$object->getAttributeLabel($attribute);

		if(($message=$this->message)===null)
			$message=Yii::t('yii','{attribute} is of the wrong length (should be {length} characters).');
		$message=strtr($message, array(
			'{attribute}'=>$label,
			'{length}'=>$this->is,
		));

		if(($tooShort=$this->tooShort)===null)
			$tooShort=Yii::t('yii','{attribute} is too short (minimum is {min} characters).');
		$tooShort=strtr($tooShort, array(
			'{attribute}'=>$label,
			'{min}'=>$this->min,
		));

		if(($tooLong=$this->tooLong)===null)
			$tooLong=Yii::t('yii','{attribute} is too long (maximum is {max} characters).');
		$tooLong=strtr($tooLong, array(
			'{attribute}'=>$label,
			'{max}'=>$this->max,
		));

		$js='';
		if($this->min!==null)
		{
			$js.="
if(value.length<{$this->min}) {
	messages.push(".CJSON::encode($tooShort).");
}
";
		}
		if($this->max!==null)
		{
			$js.="
if(value.length>{$this->max}) {
	messages.push(".CJSON::encode($tooLong).");
}
";
		}
		if($this->is!==null)
		{
			$js.="
if(value.length!={$this->is}) {
	messages.push(".CJSON::encode($message).");
}
";
		}

		if($this->allowEmpty)
		{
			$js="
if($.trim(value)!='') {
	$js
}
";
		}

		return $js;
	}
}