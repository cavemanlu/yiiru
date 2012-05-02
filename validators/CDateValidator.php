<?php
/**
 * Файл валидатора CDateValidator.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Валидатор CDateValidator проверяет соответствие атрибута дате, времени или временной отметке.
 *
 * Установкой свойства {@link format} можно определять формат значения даты.
 * Если переданное значение даты не соответствует данному формату, атрибут считается неверным.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CDateValidator.php 2799 2011-01-01 19:31:13Z qiang.xue $
 * @package system.validators
 * @since 1.1.7
 */
class CDateValidator extends CValidator
{
	/**
	 * @var mixed шаблон формата, которому должно соответствовать значение даты.
	 * Может быть строкой или массивом, представляющим несколько форматов.
	 * По умолчанию - 'MM/dd/yyyy'. Обратитесь за деталями о том, как определять формат даты,
	 * к описанию класса {@link CDateTimeParser}
	 */
	public $format='MM/dd/yyyy';
	/**
	 * @var boolean может ли атрибут быть пустым или иметь значение null. По умолчанию - true,
	 * т.е., пустой атрибут считается допустимым
	 */
	public $allowEmpty=true;
	/**
	 * @var string имя атрибута для получения результата разбора.
	 * Если даное свойство не равно null и валидация прошла успешно, атрибут будет заполнен
	 * значением результата разбора в виде временной отметки
	 */
	public $timestampAttribute;

	/**
	 * Валидирует атрибут объекта.
	 * При возникновении ошибки к объекту добавляется сообщение об ошибке.
	 * @param CModel $object валидируемый объект данных
	 * @param string $attribute имя валидируемого атрибута
	 */
	protected function validateAttribute($object,$attribute)
	{
		$value=$object->$attribute;
		if($this->allowEmpty && $this->isEmpty($value))
			return;

		$formats=is_string($this->format) ? array($this->format) : $this->format;
		$valid=false;
		foreach($formats as $format)
		{
			$timestamp=CDateTimeParser::parse($value,$format,array('month'=>1,'day'=>1,'hour'=>0,'minute'=>0,'second'=>0));
			if($timestamp!==false)
			{
				$valid=true;
				if($this->timestampAttribute!==null)
					$object->{$this->timestampAttribute}=$timestamp;
				break;
			}
		}

		if(!$valid)
		{
			$message=$this->message!==null?$this->message : Yii::t('yii','The format of {attribute} is invalid.');
			$this->addError($object,$attribute,$message);
		}
	}
}

