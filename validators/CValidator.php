<?php
/**
 * Файл класса CValidator.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Класс CValidator - это базовый класс для всех валидаторов.
 *
 * Классы-потомки должны реализовывать метод {@link validateAttribute}.
 *
 * В классе CValidator определены следующие свойства:
 * <ul>
 * <li>{@link attributes}: массив, список валидируемых атрибутов;</li>
 * <li>{@link message}: строка, настроенное сообщение об ошибке. Сообщение может содержать
 *   метки, заменяемые затем фактическим содержимым.
 *   Например, метка "{attribute}" будет заменена меткой (лейблом) проблематичного атрибута.
 *   Различные валидаторы могут определять дополнительные метки.</li>
 * <li>{@link on}: строка, в каком сценарии должен срабатывать валидатор.
 *   Используется на соответствие параметру 'on', выдаваемому при вызове метода {@link CModel::validate}.</li>
 * </ul>
 *
 * При использовании метода {@link createValidator} для создания валидатора распознаются
 * как соответствующие встроенным классам-валидаторам следующие псевдонимы:
 * <ul>
 * <li>required: {@link CRequiredValidator}</li>
 * <li>filter: {@link CFilterValidator}</li>
 * <li>match: {@link CRegularExpressionValidator}</li>
 * <li>email: {@link CEmailValidator}</li>
 * <li>url: {@link CUrlValidator}</li>
 * <li>unique: {@link CUniqueValidator}</li>
 * <li>compare: {@link CCompareValidator}</li>
 * <li>length: {@link CStringValidator}</li>
 * <li>in: {@link CRangeValidator}</li>
 * <li>numerical: {@link CNumberValidator}</li>
 * <li>captcha: {@link CCaptchaValidator}</li>
 * <li>type: {@link CTypeValidator}</li>
 * <li>file: {@link CFileValidator}</li>
 * <li>default: {@link CDefaultValueValidator}</li>
 * <li>exist: {@link CExistValidator}</li>
 * <li>boolean: {@link CBooleanValidator}</li>
 * <li>date: {@link CDateValidator}</li>
 * <li>safe: {@link CSafeValidator}</li>
 * <li>unsafe: {@link CUnsafeValidator}</li>
 * </ul>
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CValidator.php 3515 2011-12-28 12:29:24Z mdomba $
 * @package system.validators
 * @since 1.0
 */
abstract class CValidator extends CComponent
{
	/**
	 * @var array список встроенных валидаторов (имя => класс)
	 */
	public static $builtInValidators=array(
		'required'=>'CRequiredValidator',
		'filter'=>'CFilterValidator',
		'match'=>'CRegularExpressionValidator',
		'email'=>'CEmailValidator',
		'url'=>'CUrlValidator',
		'unique'=>'CUniqueValidator',
		'compare'=>'CCompareValidator',
		'length'=>'CStringValidator',
		'in'=>'CRangeValidator',
		'numerical'=>'CNumberValidator',
		'captcha'=>'CCaptchaValidator',
		'type'=>'CTypeValidator',
		'file'=>'CFileValidator',
		'default'=>'CDefaultValueValidator',
		'exist'=>'CExistValidator',
		'boolean'=>'CBooleanValidator',
		'safe'=>'CSafeValidator',
		'unsafe'=>'CUnsafeValidator',
		'date'=>'CDateValidator',
	);

	/**
	 * @var array список валидируемых атрибутов.
	 */
	public $attributes;
	/**
	 * @var string пользовательское сообщение об ошибке. Различные валидаторы могут определять
	 * разные метки в сообщении, которые затем будут заменены реальными значениями. Все валидаторы
	 * распознают метку "{attribute}", которая заменяется меткой (лейблом) атрибута.
	 */
	public $message;
	/**
	 * @var boolean должно ли данное правило валидации прекратиться, если для данного атрибута
	 * уже появились ошибки валидации. По умолчанию - false.
	 * @since 1.1.1
	 */
	public $skipOnError=false;
	/**
	 * @var array список сценариев, к которым применяется валидатор
	 * Каждое значение массива относится к имени сценария с таким же именем ключа массива.
	 */
	public $on;
	/**
	 * @var boolean должны ли атрибуты данного валидатора считаться безопасными для пакетного присваивания.
	 * По умолчанию - false.
	 * @since 1.1.4
	 */
	public $safe=true;
	/**
	 * @var boolean whether to perform client-side validation. Defaults to true.
	 * Please refer to {@link CActiveForm::enableClientValidation} for more details about client-side validation.
	 * @since 1.1.7
	 */
	public $enableClientValidation=true;

	/**
	 * Валидирует отдельный атрибут.
	 * Метод должен переопределяться классами-потомками.
	 * @param CModel $object валидируемый объект данных
	 * @param string $attribute имя валидируемого атрибута
	 */
	abstract protected function validateAttribute($object,$attribute);


	/**
	 * Создает объект валидатора.
	 * @param string $name имя или класс валидатора
	 * @param CModel $object валидируемый объект данных, который может содержать встроенный метод валидации
	 * @param mixed $attributes список валидируемых атрибутов. Может быть либо массивом имен атрибутов либо
	 * строкой имен атрибутов, разделенных запятой.
	 * @param array $params начальные значения, применяемые к свойствам валидатора
	 * @return CValidator the validator
	 */
	public static function createValidator($name,$object,$attributes,$params=array())
	{
		if(is_string($attributes))
			$attributes=preg_split('/[\s,]+/',$attributes,-1,PREG_SPLIT_NO_EMPTY);

		if(isset($params['on']))
		{
			if(is_array($params['on']))
				$on=$params['on'];
			else
				$on=preg_split('/[\s,]+/',$params['on'],-1,PREG_SPLIT_NO_EMPTY);
		}
		else
			$on=array();

		if(method_exists($object,$name))
		{
			$validator=new CInlineValidator;
			$validator->attributes=$attributes;
			$validator->method=$name;
			if(isset($params['clientValidate']))
			{
				$validator->clientValidate=$params['clientValidate'];
				unset($params['clientValidate']);
			}
			$validator->params=$params;
			if(isset($params['skipOnError']))
				$validator->skipOnError=$params['skipOnError'];
		}
		else
		{
			$params['attributes']=$attributes;
			if(isset(self::$builtInValidators[$name]))
				$className=Yii::import(self::$builtInValidators[$name],true);
			else
				$className=Yii::import($name,true);
			$validator=new $className;
			foreach($params as $name=>$value)
				$validator->$name=$value;
		}

		$validator->on=empty($on) ? array() : array_combine($on,$on);

		return $validator;
	}

	/**
	 * Валидирует определенный объект.
	 * @param CModel $object валидируемый объект данных
	 * @param array $attributes валидируемый объект данных. По умолчанию - null, т.е.
	 * будет валидироваться каждый атрибут в списке {@link attributes}.
	 */
	public function validate($object,$attributes=null)
	{
		if(is_array($attributes))
			$attributes=array_intersect($this->attributes,$attributes);
		else
			$attributes=$this->attributes;
		foreach($attributes as $attribute)
		{
			if(!$this->skipOnError || !$object->hasErrors($attribute))
				$this->validateAttribute($object,$attribute);
		}
	}

	/**
	 * Returns the JavaScript needed for performing client-side validation.
	 * Do not override this method if the validator does not support client-side validation.
	 * Two predefined JavaScript variables can be used:
	 * <ul>
	 * <li>value: the value to be validated</li>
	 * <li>messages: an array used to hold the validation error messages for the value</li>
	 * </ul>
	 * @param CModel $object the data object being validated
	 * @param string $attribute the name of the attribute to be validated.
	 * @return string the client-side validation script. Null if the validator does not support client-side validation.
	 * @see CActiveForm::enableClientValidation
	 * @since 1.1.7
	 */
	public function clientValidateAttribute($object,$attribute)
	{
	}

	/**
	 * Возвращает значение, показывающее, применяется ли валидатор к определенному сценарию.
	 * Валидатор применяется к сценарию в случае, если выоплняются следующие условия:
	 * <ul>
	 * <li>свойство "on" валидатора пусто</li>
	 * <li>свойство "on" валидатора содержит определенный сценарий</li>
	 * </ul>
	 * @param string $scenario имя сценария
	 * @return boolean применяется ли валидатор к определенному сценарию
	 */
	public function applyTo($scenario)
	{
		return empty($this->on) || isset($this->on[$scenario]);
	}

	/**
	 * Добавляет ошибку об определенном атрибуте в active record-объект.
	 * Это вспомогательный метод, выполняющий выборку и интернационализацию сообщения.
	 * @param CModel $object валидируемый объект данных
	 * @param string $attribute валидируемый атрибут
	 * @param string $message сообщение об ошибке
	 * @param array $params значения для меток в сообщении об ошибке
	 */
	protected function addError($object,$attribute,$message,$params=array())
	{
		$params['{attribute}']=$object->getAttributeLabel($attribute);
		$object->addError($attribute,strtr($message,$params));
	}

	/**
	 * Проверяет, пусто ли переданное значение.
	 * Значение считается пустым, если оно равно null, пустому массиву или пустой строке (предварительно усеченной с обеих сторон).
	 * Примечание: метод отличается от встроенного метода PHP - empty(). Он возвращает false, если значение равно 0.
	 * @param mixed $value проверяемое значение
	 * @param boolean $trim выполнять ли усечение перед проверкой строки. По умолчанию - false.
	 * @return boolean пусто ли значение
	 */
	protected function isEmpty($value,$trim=false)
	{
		return $value===null || $value===array() || $value==='' || $trim && is_scalar($value) && trim($value)==='';
	}
}

