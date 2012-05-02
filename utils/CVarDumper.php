<?php
/**
 * Файл класса CVarDumper.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Класс CVarDumper предназначен для замены PHP-функций var_dump и print_r.
 * Он может корректно определять рекурсивно связанные объекты в сложных объектных структурах.
 * Также он имеет управление глубиной рекурсии во избежание бесконечной рекурсии некоторых
 * специфичных переменных.
 *
 * Класс CVarDumper может использоваться так:
 * <pre>
 * CVarDumper::dump($var);
 * </pre>
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CVarDumper.php 2799 2011-01-01 19:31:13Z qiang.xue $
 * @package system.utils
 * @since 1.0
 */
class CVarDumper
{
	private static $_objects;
	private static $_output;
	private static $_depth;

	/**
	 * Отображает переменную.
	 * Метод имеет ту же функциональность, что и функции var_dump и print_r,
	 * но более надежен при обработке сложных объектов, таких как контроллеры Yii.
	 * @param mixed $var переменная
	 * @param integer $depth максимальная глубина, на которую может спуститься метод при обходе переменной. По умолчанию - 10.
	 * @param boolean $highlight должен ли результат быть обработан подсветкой синтаксиса
	 */
	public static function dump($var,$depth=10,$highlight=false)
	{
		echo self::dumpAsString($var,$depth,$highlight);
	}

	/**
	 * Преобразует переменную в строку.
	 * Метод имеет ту же функциональность, что и функции var_dump и print_r,
	 * но более надежен при обработке сложных объектов, таких как контроллеры Yii.
	 * @param mixed $var переменная
	 * @param integer $depth максимальная глубина, на которую может спуститься метод при обходе переменной. По умолчанию - 10.
	 * @param boolean $highlight должен ли результат быть обработан подсветкой синтаксиса
	 * @return string строка, представляющая переменную
	 */
	public static function dumpAsString($var,$depth=10,$highlight=false)
	{
		self::$_output='';
		self::$_objects=array();
		self::$_depth=$depth;
		self::dumpInternal($var,0);
		if($highlight)
		{
			$result=highlight_string("<?php\n".self::$_output,true);
			self::$_output=preg_replace('/&lt;\\?php<br \\/>/','',$result,1);
		}
		return self::$_output;
	}

	/*
	 * @param mixed $var variable to be dumped
	 * @param integer $level depth level
	 */
	private static function dumpInternal($var,$level)
	{
		switch(gettype($var))
		{
			case 'boolean':
				self::$_output.=$var?'true':'false';
				break;
			case 'integer':
				self::$_output.="$var";
				break;
			case 'double':
				self::$_output.="$var";
				break;
			case 'string':
				self::$_output.="'".addslashes($var)."'";
				break;
			case 'resource':
				self::$_output.='{resource}';
				break;
			case 'NULL':
				self::$_output.="null";
				break;
			case 'unknown type':
				self::$_output.='{unknown}';
				break;
			case 'array':
				if(self::$_depth<=$level)
					self::$_output.='array(...)';
				else if(empty($var))
					self::$_output.='array()';
				else
				{
					$keys=array_keys($var);
					$spaces=str_repeat(' ',$level*4);
					self::$_output.="array\n".$spaces.'(';
					foreach($keys as $key)
					{
						$key2=str_replace("'","\\'",$key);
						self::$_output.="\n".$spaces."    '$key2' => ";
						self::$_output.=self::dumpInternal($var[$key],$level+1);
					}
					self::$_output.="\n".$spaces.')';
				}
				break;
			case 'object':
				if(($id=array_search($var,self::$_objects,true))!==false)
					self::$_output.=get_class($var).'#'.($id+1).'(...)';
				else if(self::$_depth<=$level)
					self::$_output.=get_class($var).'(...)';
				else
				{
					$id=array_push(self::$_objects,$var);
					$className=get_class($var);
					$members=(array)$var;
					$spaces=str_repeat(' ',$level*4);
					self::$_output.="$className#$id\n".$spaces.'(';
					foreach($members as $key=>$value)
					{
						$keyDisplay=strtr(trim($key),array("\0"=>':'));
						self::$_output.="\n".$spaces."    [$keyDisplay] => ";
						self::$_output.=self::dumpInternal($value,$level+1);
					}
					self::$_output.="\n".$spaces.')';
				}
				break;
		}
	}
}
