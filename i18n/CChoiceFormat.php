<?php
/**
 * Файл класса CChoiceFormat.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */


/**
 * CChoiceFormat - это класс-помощник, выбирающий подходящее сообщение основываясь на переданном значении числа.
 * Сообщение для выбора передается в виде строки следующего формата:
 * <pre>
 * 'expr1#message1|expr2#message2|expr3#message3'
 * </pre>
 * где каждое выражение (expr) должно являться верным выражением PHP с единственной переменной 'n'.
 * Например, 'n==1' и 'n%10==2 && n>10' - допустимые выражения.
 * Переменная 'n' принимает переданное значение и, если выражение принимает значение true,
 * будет возвращено соответствующее сообщение.
 *
 * Например, передав в проверяемое сообщение 'n==1#один|n==2#два|n>2#больше' и
 * число, получим в результате 'два'.
 *
 * Для выражений вида 'n==1' мы можем использовать короткий вид '1'. Так что сообщение в примере выше
 * может быть упрощено до '1#один|2#два|n>2#больше'.
 *
 * В случае, если по переданому значению не будет соответствия в сообщении, то будет
 * возвращена последняя часть переданного сообщения.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CChoiceFormat.php 3515 2011-12-28 12:29:24Z mdomba $
 * @package system.i18n
 */
class CChoiceFormat
{
	/**
	 * Форматирует сообщение согласно значению переданного числа.
	 * @param string $messages сообщение-кандидат в формате 'expr1#message1|expr2#message2|expr3#message3'.
	 * За подробностями обратитесь к {@link CChoiceFormat}.
	 * @param mixed $number проверочное число
	 * @return string выбранное сообщение
	 */
	public static function format($messages, $number)
	{
		$n=preg_match_all('/\s*([^#]*)\s*#([^\|]*)\|/',$messages.'|',$matches);
		if($n===0)
			return $messages;
		for($i=0;$i<$n;++$i)
		{
			$expression=$matches[1][$i];
			$message=$matches[2][$i];
			if($expression===(string)(int)$expression)
			{
				if($expression==$number)
					return $message;
			}
			else if(self::evaluate(str_replace('n','$n',$expression),$number))
				return $message;
		}
		return $message; // return the last choice
	}

	/**
	 * Вычисляет результат выражения, используя значение числа n.
	 * @param string $expression выражение PHP
	 * @param mixed $n проверочное число
	 * @return boolean результат выражения
	 */
	protected static function evaluate($expression,$n)
	{
		return @eval("return $expression;");
	}
}