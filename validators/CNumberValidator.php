<?php
/**
 * Файл класса CNumberValidator.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Валидатор CNumberValidator проверяет, что значение атрибута является числом.
 *
 * В дополнение к свойству {@link message} для установки пользовательского
 * сообщения об ошибке, CNumberValidator имеет еще два вида пользовательских
 * сообщений, которые можно установить согласно различным сценариям валидации.
 * Для установки пользовательского сообщения об ошибке о том, что числовое
 * значение слишком большое, можно использовать свойство {@link tooBig}.
 * Аналогично свойство {@link tooSmall} для установки сообщения в случае
 * слишком маленького числа. Эти сообщения содержат дополнительные метки,
 * заменяемые реальным содержимым. В дополнение к метке "{attribute}",
 * распознаваемой всеми валидаторами (see {@link CValidator}), CNumberValidator
 * позволяет определять следующие метки:
 * <ul>
 * <li>{min}: при использовании {@link tooSmall} заменяется нижним пределом числа - {@link min};</li>
 * <li>{max}: при использовании {@link tooBig} заменяется верхним пределом числа - {@link max}.</li>
 * </ul>
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CNumberValidator.php 3491 2011-12-17 05:17:57Z jefftulsa $
 * @package system.validators
 * @since 1.0
 */
class CNumberValidator extends CValidator
{
	/**
	 * @var boolean только ли целочисленное может быть значение атрибута. По умолчанию - false.
	 */
	public $integerOnly=false;
	/**
	 * @var boolean может ли быть значение атрибута пустым или равным null. По умолчанию - true,
	 * т.е. пустой атрибут считается валидным
	 */
	public $allowEmpty=true;
	/**
	 * @var integer|float верхняя граница числа. По умолчанию - null, т.е. без верхней границы
	 */
	public $max;
	/**
	 * @var integer|float нижняя граница числа. По умолчанию - null, т.е. без нижней границы
	 */
	public $min;
	/**
	 * @var string пользовательское сообщение об ошибке, если значение слишком большое
	 */
	public $tooBig;
	/**
	 * @var string пользовательское сообщение об ошибке, если значение слишком маленькое
	 */
	public $tooSmall;
	/**
	 * @var string регулярное выражение для определение целых чисел
	 * @since 1.1.7
	 */
	public $integerPattern='/^\s*[+-]?\d+\s*$/';
	/**
	 * @var string регулярное выражение для определения чисел
	 * @since 1.1.7
	 */
	public $numberPattern='/^\s*[-+]?[0-9]*\.?[0-9]+([eE][-+]?[0-9]+)?\s*$/';


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
		if($this->integerOnly)
		{
			if(!preg_match($this->integerPattern,"$value"))
			{
				$message=$this->message!==null?$this->message:Yii::t('yii','{attribute} must be an integer.');
				$this->addError($object,$attribute,$message);
			}
		}
		else
		{
			if(!preg_match($this->numberPattern,"$value"))
			{
				$message=$this->message!==null?$this->message:Yii::t('yii','{attribute} must be a number.');
				$this->addError($object,$attribute,$message);
			}
		}
		if($this->min!==null && $value<$this->min)
		{
			$message=$this->tooSmall!==null?$this->tooSmall:Yii::t('yii','{attribute} is too small (minimum is {min}).');
			$this->addError($object,$attribute,$message,array('{min}'=>$this->min));
		}
		if($this->max!==null && $value>$this->max)
		{
			$message=$this->tooBig!==null?$this->tooBig:Yii::t('yii','{attribute} is too big (maximum is {max}).');
			$this->addError($object,$attribute,$message,array('{max}'=>$this->max));
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
			$message=$this->integerOnly ? Yii::t('yii','{attribute} must be an integer.') : Yii::t('yii','{attribute} must be a number.');
		$message=strtr($message, array(
			'{attribute}'=>$label,
		));

		if(($tooBig=$this->tooBig)===null)
			$tooBig=Yii::t('yii','{attribute} is too big (maximum is {max}).');
		$tooBig=strtr($tooBig, array(
			'{attribute}'=>$label,
			'{max}'=>$this->max,
		));

		if(($tooSmall=$this->tooSmall)===null)
			$tooSmall=Yii::t('yii','{attribute} is too small (minimum is {min}).');
		$tooSmall=strtr($tooSmall, array(
			'{attribute}'=>$label,
			'{min}'=>$this->min,
		));

		$pattern=$this->integerOnly ? $this->integerPattern : $this->numberPattern;
		$js="
if(!value.match($pattern)) {
	messages.push(".CJSON::encode($message).");
}
";
		if($this->min!==null)
		{
			$js.="
if(value<{$this->min}) {
	messages.push(".CJSON::encode($tooSmall).");
}
";
		}
		if($this->max!==null)
		{
			$js.="
if(value>{$this->max}) {
	messages.push(".CJSON::encode($tooBig).");
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
