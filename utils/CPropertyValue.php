<?php
/**
 * Файл класса CPropertyValue.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Класс CPropertyValue - это вспомогательный класс, предоставляющий методы для конвертации значений
 * свойств компонентов в определенный тип.
 *
 * Обычно класс CPropertyValue используется в сетерах компонента для строгого соответствия
 * значения нового свойства определенному типу.
 * Например, сеттер свойства булева типа может выглядеть так:
 * <pre>
 * public function setPropertyName($value)
 * {
 *     $value=CPropertyValue::ensureBoolean($value);
 *     // $value теперь имеет булев тип
 * }
 * </pre>
 *
 * Свойства могут быть следующими типами с особенными правилами конвертации:
 * <ul>
 * <li>string: булево значение будет конвертировано в 'true' или 'false';</li>
 * <li>boolean: строка 'true' (регистронезависимо) будет конвертирована в значение true,
 * а строка 'false' (регистронезависимо) - в значение false;</li>
 * <li>integer;</li>
 * <li>float;</li>
 * <li>array: строка, начинающаяся '(' и заканчивающаяся ')' будет считаться выражением массива,
 *          которое будет выполнено. Иначе будет возвращен массив с одним элементом;</li>
 * <li>object;</li>
 * <li>enum: перечисляемый тип, представленный строкой или массивом.</li>
 * </ul>
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CPropertyValue.php 2799 2011-01-01 19:31:13Z qiang.xue $
 * @package system.utils
 * @since 1.0
 */
class CPropertyValue
{
	/**
	 * Конвертирует значение в булево значение.
	 * Примечание: строка 'true' (регистронезависимо) будет конвертирована в значение true,
	 * а строка 'false' (регистронезависимо) - в значение false.
	 * Если строка представляет собой ненулевое число, она будет интерпретирована как true.
	 * @param mixed $value значение для конвертации.
	 * @return boolean булево значение
	 */
	public static function ensureBoolean($value)
	{
		if (is_string($value))
			return !strcasecmp($value,'true') || $value!=0;
		else
			return (boolean)$value;
	}

	/**
	 * Конвертирует значение в строку.
	 * Примечание: булево значение будет преобразовано в 'true', если оно истинно
	 * и в 'false', если ложно.
	 * @param mixed $value значение для конвертации.
	 * @return string строка
	 */
	public static function ensureString($value)
	{
		if (is_bool($value))
			return $value?'true':'false';
		else
			return (string)$value;
	}

	/**
	 * Конвертирует значение в целое число (integer).
	 * @param mixed $value значение для конвертации.
	 * @return integer целое число (integer)
	 */
	public static function ensureInteger($value)
	{
		return (integer)$value;
	}

	/**
	 * Конвертирует значение в число с плавающей точкой (float).
	 * @param mixed $value значение для конвертации.
	 * @return float число с плавающей точкой (float)
	 */
	public static function ensureFloat($value)
	{
		return (float)$value;
	}

	/**
	 * Конвертирует значение в массив. Если значени - это строка вида
	 * '(a,b,c)', тогда будет возвращен массив с элементами a, b и c.
	 * В данном случае можно использовать и вложенные массивы, т.к. преобразование
	 * происходит функцией eval.
	 * Если строка имеет другой вид, то массив будет содержать один элемент - эту строку.
	 * Если значение не является строкой, тогда будет возвращен массив с одним элементом - этим значением.
	 * @param mixed $value значение для конвертации.
	 * @return array массив
	 */
	public static function ensureArray($value)
	{
		if(is_string($value))
		{
			$value = trim($value);
			$len = strlen($value);
			if ($len >= 2 && $value[0] == '(' && $value[$len-1] == ')')
			{
				eval('$array=array'.$value.';');
				return $array;
			}
			else
				return $len>0?array($value):array();
		}
		else
			return (array)$value;
	}

	/**
	 * Конвертирует значение в объектный тип.
	 * @param mixed $value значение для конвертации.
	 * @return object объект
	 */
	public static function ensureObject($value)
	{
		return (object)$value;
	}

	/**
	 * Конвертирует значение в перечисляемый тип.
	 *
	 * Метод проверяет, является ли значение определенным перечисляемым типом.
	 * Значение является правильным перечисляемым значением, если оно равно имени некоторой константы
	 * в переданном перечисляемом типе (классе).
	 * За подробностями обратитесь к перечисляемым типам - {@link CEnumerable}.
	 *
	 * @param string $value проверяемое перечисляемое значение.
	 * @param string $enumType имя класса перечисляемого типа (убедитесь, что код этого класса был включен до вызова данной функции).
	 * @return string правильное перечисляемое значение
	 * @throws CException вызывается, если значение не является правильным перечислояемым значением
	 */
	public static function ensureEnum($value,$enumType)
	{
		static $types=array();
		if(!isset($types[$enumType]))
			$types[$enumType]=new ReflectionClass($enumType);
		if($types[$enumType]->hasConstant($value))
			return $value;
		else
			throw new CException(Yii::t('yii','Invalid enumerable value "{value}". Please make sure it is among ({enum}).',
				array('{value}'=>$value, '{enum}'=>implode(', ',$types[$enumType]->getConstants()))));
	}
}
