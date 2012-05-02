<?php
/**
 * Файл класса YiiBase.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 * @version $Id: YiiBase.php 3597 2012-02-19 21:22:08Z qiang.xue@gmail.com $
 * @package system
 * @since 1.0
 */

/**
 * Получает временную отметку начала работы приложения.
 */
defined('YII_BEGIN_TIME') or define('YII_BEGIN_TIME',microtime(true));
/**
 * Данная константа определяет, находится ли приложения в режиме отладки. По
 * умолчанию - false.
 */
defined('YII_DEBUG') or define('YII_DEBUG',false);
/**
 * Данная константа определяет количество информации (имя файла и номер строки)
 * стека вызовов должно журналироваться методом Yii::trace(). По умолчанию - 0,
 * т.е. без информации стека. Если значение больше 0, стек вызовов будет
 * журналироваться не глубже данного числа. Помните, рассматриваются только
 * стеки вызовов пользовательского приложения.
 */
defined('YII_TRACE_LEVEL') or define('YII_TRACE_LEVEL',0);
/**
 * Данная константа определяет, должна ли быть включена обработка исключений.
 * По умолчанию - true.
 */
defined('YII_ENABLE_EXCEPTION_HANDLER') or define('YII_ENABLE_EXCEPTION_HANDLER',true);
/**
 * Данная константа определяет, должна ли быть включена обработка ошибок.
 * По умолчанию - true.
 */
defined('YII_ENABLE_ERROR_HANDLER') or define('YII_ENABLE_ERROR_HANDLER',true);
/**
 * Определяет установочный путь фреймворка Yii.
 */
defined('YII_PATH') or define('YII_PATH',dirname(__FILE__));
/**
 * Определяет установочный путь библиотеки расширений Zii.
 */
defined('YII_ZII_PATH') or define('YII_ZII_PATH',YII_PATH.DIRECTORY_SEPARATOR.'zii');

/**
 * YiiBase - это вспомогательный класс, предоставляющий общий функционал
 * фреймворка.
 *
 * Не используйте класс YiiBase напрямую. Вместо этого используйте класс-потомок
 * {@link Yii}, где вы можете настраивать методы YiiBase
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: YiiBase.php 3597 2012-02-19 21:22:08Z qiang.xue@gmail.com $
 * @package system
 * @since 1.0
 */
class YiiBase
{
	/**
	 * @var array карта классов, используемая для механизма автозагрузки классов.
	 * Ключи массива - имена классов, а значения - соответствующие файловые пути
	 * @since 1.1.5
	 */
	public static $classMap=array();
	/**
	 * @var boolean обращаться ли к путям влючения PHP для автоподгрузки файлов
	 * классов. По умолчанию - true. Вы можете установить свойство в значение
	 * false, если ваше хостинг не поддерживает изменение путей включения PHP
	 * или если вы хотите добавить дополнительные автоподгрузчики классов к
	 * встроенному в Yii автоподгрузчику
	 * @since 1.1.8
	 */
	public static $enableIncludePath=true;

	private static $_aliases=array('system'=>YII_PATH,'zii'=>YII_ZII_PATH); // alias => path
	private static $_imports=array();					// alias => class name or directory
	private static $_includePaths;						// list of include paths
	private static $_app;
	private static $_logger;



	/**
	 * @return string версия фреймворка Yii
	 */
	public static function getVersion()
	{
		return '1.1.11-dev';
	}

	/**
	 * Создает экземпляр веб-приложения.
	 * @param mixed $config конфигурация приложения.
	 * Если передается строка, она считается путем к файлу, содержащему массив
	 * конфигурации; если передается массив, он считается информацией
	 * конфигурации. Убедитесь в правильности установки свойства
	 * {@link CApplication::basePath basePath} в конфигурации, которое должно
	 * указывать на директорию, содержащую всю логику приложения, шаблоны и
	 * данные. Если свойство не установлено, директорией по умолчанию будет
	 * 'protected'
	 * @return CWebApplication
	 */
	public static function createWebApplication($config=null)
	{
		return self::createApplication('CWebApplication',$config);
	}

	/**
	 * Создает экземпляр консольного приложения.
	 * @param mixed $config конфигурация приложения.
	 * Если передается строка, она считается путем к файлу, содержащему массив конфигурации;
	 * если передается массив, он считается информацией конфигурации.
	 * Убедитесь в правильности установки свойства {@link CApplication::basePath basePath} в конфигурации,
	 * которое должно указывать на директорию, содержащую всю логику приложения, шаблоны и данные.
	 * Если свойство не установлено, директорией по умолчанию будет 'protected'.
	 * @return CConsoleApplication
	 */
	public static function createConsoleApplication($config=null)
	{
		return self::createApplication('CConsoleApplication',$config);
	}

	/**
	 * Создает приложения по определенному классу
	 * @param string $class имя класса приложения
	 * @param mixed $config конфигурация приложения. Данный параметр будет
	 * передан как параметр в конструктор класса приложения
	 * @return mixed экземпляр приложения
	 */
	public static function createApplication($class,$config=null)
	{
		return new $class($config);
	}

	/**
	 * Возвращает синглтон приложения; null, если синглтон еще не был создан
	 * @return CApplication синглтон приложения; null, если синглтон еще не был создан
	 */
	public static function app()
	{
		return self::$_app;
	}

	/**
	 * Сохраняет экземпляр приложения в статическом члене класса.
	 * Метод помогает реализовать шаблон проектирования "синглтон" для CApplication.
	 * Повторный вызов данного метода либо конструктора CApplication
	 * будет вызывать исключение.
	 * Для получения экземпляра приложения используйте метод {@link app()}.
	 * @param CApplication $app экземпляр приложения. Если null, существующий
	 * синглтон приложения будет удален
	 * @throws CException вызывается, если экземпляр приложения уже существует
	 */
	public static function setApplication($app)
	{
		if(self::$_app===null || $app===null)
			self::$_app=$app;
		else
			throw new CException(Yii::t('yii','Yii application can only be created once.'));
	}

	/**
	 * @return string путь к фреймворку
	 */
	public static function getFrameworkPath()
	{
		return YII_PATH;
	}

	/**
	 * Создает компонент и инициализирует его, основываясь на переданной конфигурации.
	 *
	 * Передаваемая конфигурация может быть либо строкой либо массивом.
	 * Строка считается типом объекта, который может быть либо именем класса либо
	 * {@link YiiBase::getPathOfAlias псевдонимом пути к классу}.
	 * Если передан массив, его элемент 'class' считается типом объекта,
	 * а последующие пары имя-значение используются для инициализации соответствующих
	 * свойств объекта.
	 *
	 * Все дополнительные параметры, переданные методу, будут переданы в конструктор
	 * создаваемого объекта
	 *
	 * @param mixed $config конфигурация. Можеть либо строкой либо массивом
	 * @return mixed созданный объект
	 * @throws CException вызывается, если массив конфигурации не имеет элемента с имененм 'class'
	 */
	public static function createComponent($config)
	{
		if(is_string($config))
		{
			$type=$config;
			$config=array();
		}
		else if(isset($config['class']))
		{
			$type=$config['class'];
			unset($config['class']);
		}
		else
			throw new CException(Yii::t('yii','Object configuration must be an array containing a "class" element.'));

		if(!class_exists($type,false))
			$type=Yii::import($type,true);

		if(($n=func_num_args())>1)
		{
			$args=func_get_args();
			if($n===2)
				$object=new $type($args[1]);
			else if($n===3)
				$object=new $type($args[1],$args[2]);
			else if($n===4)
				$object=new $type($args[1],$args[2],$args[3]);
			else
			{
				unset($args[0]);
				$class=new ReflectionClass($type);
				// Note: ReflectionClass::newInstanceArgs() is available for PHP 5.1.3+
				// $object=$class->newInstanceArgs($args);
				$object=call_user_func_array(array($class,'newInstance'),$args);
			}
		}
		else
			$object=new $type;

		foreach($config as $key=>$value)
			$object->$key=$value;

		return $object;
	}

	/**
	 * Импортирует определение класса или директории файлаов класса.
	 *
	 * Импорт класса похож на включение соответствующего файла класса.
	 * Главное отличие - это то, что импорт класса намного легче, т.к.
	 * файл класса подключается только при первом обращении к классу.
	 *
	 * Импорт директории равносилен добавлению директории в путь включения PHP (include path).
	 * Если импортируется несколько директорий, то директория, импортируемая последней, будет
	 * иметь приоритет при поиске файла класса (т.е., она добавляется в начало пути включения PHP (include path).
	 *
	 * Для импортирования файлов класса или директории используются псевдонимы пути. Например,
	 * <ul>
	 *   <li><code>application.components.GoogleMap</code>: импортирует класс <code>GoogleMap</code>;</li>
	 *   <li><code>application.components.*</code>: импортирует директорию <code>components</code>.</li>
	 * </ul>
	 *
	 * Один и тот же псевдоним может быть импортирован несколько раз, но значение имеет только первый.
	 * При импорте директории ее поддиректории не импортируются.
	 *
	 * Начиная с версии 1.1.5, метод также может быть использован для импорта классов в формате пространства имен (namespace,
	 * доступно только для PHP версии 5.3 или выше). Данный способ похож на импорт классов в формате псевдонима путей,
	 * за исключением того, что разделителем является обратный слеш, а не точка. Например, строка импорта
	 * <code>application\components\GoogleMap</code> похожа на <code>application.components.GoogleMap</code>.
	 * Различие в том, что первый класс имеет полностью однозначное имя, в то время как второй нет.
	 *
	 * Примечание: импорт классов в формате пространства имен требует, чтобы пространство имен соответствовало
	 * правильному псевдониму пути при замене обратного слеша на точку.
	 * Например, пространство имен <code>application\components</code> должно соответствовать правильному псевдониму
	 * пути <code>application.components</code>.
	 *
	 * @param string $alias псевдоним импортируемого пути
	 * @param boolean $forceInclude включать ли файл класс немедленно. Если false, файл класса
	 * будут подгружен только при первом использовании класса. Параметр используется только если
	 * псевдоним пути ссылается на класс
	 * @return string имя класса или директория, на которые указывает псевдоним
	 * @throws CException вызывается, если псевдоним невалиден
	 */
	public static function import($alias,$forceInclude=false)
	{
		if(isset(self::$_imports[$alias]))  // previously imported
			return self::$_imports[$alias];

		if(class_exists($alias,false) || interface_exists($alias,false))
			return self::$_imports[$alias]=$alias;

		if(($pos=strrpos($alias,'\\'))!==false) // a class name in PHP 5.3 namespace format
		{
			$namespace=str_replace('\\','.',ltrim(substr($alias,0,$pos),'\\'));
			if(($path=self::getPathOfAlias($namespace))!==false)
			{
				$classFile=$path.DIRECTORY_SEPARATOR.substr($alias,$pos+1).'.php';
				if($forceInclude)
				{
					if(is_file($classFile))
						require($classFile);
					else
						throw new CException(Yii::t('yii','Alias "{alias}" is invalid. Make sure it points to an existing PHP file.',array('{alias}'=>$alias)));
					self::$_imports[$alias]=$alias;
				}
				else
					self::$classMap[$alias]=$classFile;
				return $alias;
			}
			else
				throw new CException(Yii::t('yii','Alias "{alias}" is invalid. Make sure it points to an existing directory.',
					array('{alias}'=>$namespace)));
		}

		if(($pos=strrpos($alias,'.'))===false)  // a simple class name
		{
			if($forceInclude && self::autoload($alias))
				self::$_imports[$alias]=$alias;
			return $alias;
		}

		$className=(string)substr($alias,$pos+1);
		$isClass=$className!=='*';

		if($isClass && (class_exists($className,false) || interface_exists($className,false)))
			return self::$_imports[$alias]=$className;

		if(($path=self::getPathOfAlias($alias))!==false)
		{
			if($isClass)
			{
				if($forceInclude)
				{
					if(is_file($path.'.php'))
						require($path.'.php');
					else
						throw new CException(Yii::t('yii','Alias "{alias}" is invalid. Make sure it points to an existing PHP file.',array('{alias}'=>$alias)));
					self::$_imports[$alias]=$className;
				}
				else
					self::$classMap[$className]=$path.'.php';
				return $className;
			}
			else  // a directory
			{
				if(self::$_includePaths===null)
				{
					self::$_includePaths=array_unique(explode(PATH_SEPARATOR,get_include_path()));
					if(($pos=array_search('.',self::$_includePaths,true))!==false)
						unset(self::$_includePaths[$pos]);
				}

				array_unshift(self::$_includePaths,$path);

				if(self::$enableIncludePath && set_include_path('.'.PATH_SEPARATOR.implode(PATH_SEPARATOR,self::$_includePaths))===false)
					self::$enableIncludePath=false;

				return self::$_imports[$alias]=$path;
			}
		}
		else
			throw new CException(Yii::t('yii','Alias "{alias}" is invalid. Make sure it points to an existing directory or file.',
				array('{alias}'=>$alias)));
	}

	/**
	 * Транслирует псевдоним в файловый путь.
	 * Примечание: метод не гарантирует существование результирующего файлового пути.
	 * Он только проверяет, является ли валидным корневой псевдоним.
	 * @param string $alias псевдоним (например, system.web.CController)
	 * @return mixed файловый путь, соответствующий псевдониму; false, если псевдоним невалиден
	 */
	public static function getPathOfAlias($alias)
	{
		if(isset(self::$_aliases[$alias]))
			return self::$_aliases[$alias];
		else if(($pos=strpos($alias,'.'))!==false)
		{
			$rootAlias=substr($alias,0,$pos);
			if(isset(self::$_aliases[$rootAlias]))
				return self::$_aliases[$alias]=rtrim(self::$_aliases[$rootAlias].DIRECTORY_SEPARATOR.str_replace('.',DIRECTORY_SEPARATOR,substr($alias,$pos+1)),'*'.DIRECTORY_SEPARATOR);
			else if(self::$_app instanceof CWebApplication)
			{
				if(self::$_app->findModule($rootAlias)!==null)
					return self::getPathOfAlias($alias);
			}
		}
		return false;
	}

	/**
	 * Создает псевдонимы пути.
	 * Примечание: метод не проверяет существование пути и не нормализует путь.
	 * @param string $alias псевдоним пути
	 * @param string $path путь, соответствующий псевдониму. Если null, соответствующий псевдоним пути будет удален
	 */
	public static function setPathOfAlias($alias,$path)
	{
		if(empty($path))
			unset(self::$_aliases[$alias]);
		else
			self::$_aliases[$alias]=rtrim($path,'\\/');
	}

	/**
	 * Автозагрузчик классов.
	 * Метод заменяет "магический" метод __autoload().
	 * @param string $className имя класса
	 * @return boolean загружен ли класс
	 */
	public static function autoload($className)
	{
		// use include so that the error PHP file may appear
		if(isset(self::$classMap[$className]))
			include(self::$classMap[$className]);
		else if(isset(self::$_coreClasses[$className]))
			include(YII_PATH.self::$_coreClasses[$className]);
		else
		{
			// include class file relying on include_path
			if(strpos($className,'\\')===false)  // class without namespace
			{
				if(self::$enableIncludePath===false)
				{
					foreach(self::$_includePaths as $path)
					{
						$classFile=$path.DIRECTORY_SEPARATOR.$className.'.php';
						if(is_file($classFile))
						{
							include($classFile);
							break;
						}
					}
				}
				else
					include($className.'.php');
			}
			else  // class name with namespace in PHP 5.3
			{
				$namespace=str_replace('\\','.',ltrim($className,'\\'));
				if(($path=self::getPathOfAlias($namespace))!==false)
					include($path.'.php');
				else
					return false;
			}
			return class_exists($className,false) || interface_exists($className,false);
		}
		return true;
	}

	/**
	 * Записывает трассирующее сообщение.
	 * Метод журналирует сообщение только если приложение находится в режиме отладки.
	 * @param string $msg журналируемое сообщение
	 * @param string $category категория сообщения
	 * @see log
	 */
	public static function trace($msg,$category='application')
	{
		if(YII_DEBUG)
			self::log($msg,CLogger::LEVEL_TRACE,$category);
	}

	/**
	 * Журналирует сообщение.
	 * Сообщения, журналируемые данным методом, могут быть получены методом {@link CLogger::getLogs}
	 * и могут быть записаны в другие ресурсы, такие как файлы, email-адреса, БД, используя класс {@link CLogRouter}.
	 * @param string $msg журналируемое сообщение
	 * @param string $level уровень сообщения (например, 'trace', 'warning', 'error'). Регистронезависим
	 * @param string $category категория сообщения (например, 'system.web'). Регистронезависим
	 */
	public static function log($msg,$level=CLogger::LEVEL_INFO,$category='application')
	{
		if(self::$_logger===null)
			self::$_logger=new CLogger;
		if(YII_DEBUG && YII_TRACE_LEVEL>0 && $level!==CLogger::LEVEL_PROFILE)
		{
			$traces=debug_backtrace();
			$count=0;
			foreach($traces as $trace)
			{
				if(isset($trace['file'],$trace['line']) && strpos($trace['file'],YII_PATH)!==0)
				{
					$msg.="\nin ".$trace['file'].' ('.$trace['line'].')';
					if(++$count>=YII_TRACE_LEVEL)
						break;
				}
			}
		}
		self::$_logger->log($msg,$level,$category);
	}

	/**
	 * Отмечает начало блока кода для профилирования.
	 * Должен быть связан с вызовом метода {@link endProfile()} с тем же маркером (token).
	 * Вызовы начала и конца вложенных блоков должны быть правильно согласованы, например,
	 * <pre>
	 * Yii::beginProfile('block1');
	 * Yii::beginProfile('block2');
	 * Yii::endProfile('block2');
	 * Yii::endProfile('block1');
	 * </pre>
	 * Следующая последовательность неверна:
	 * <pre>
	 * Yii::beginProfile('block1');
	 * Yii::beginProfile('block2');
	 * Yii::endProfile('block1');
	 * Yii::endProfile('block2');
	 * </pre>
	 * @param string $token маркер блока кода
	 * @param string $category категория данного журналируемого сообщения
	 * @see endProfile
	 */
	public static function beginProfile($token,$category='application')
	{
		self::log('begin:'.$token,CLogger::LEVEL_PROFILE,$category);
	}

	/**
	 * Отмечает конец блока кода для профилирования.
	 * Должен быть связан с вызовом метода {@link beginProfile()} с тем же маркером (token).
	 * @param string $token маркер блока кода
	 * @param string $category категория данного журналируемого сообщения
	 * @see beginProfile
	 */
	public static function endProfile($token,$category='application')
	{
		self::log('end:'.$token,CLogger::LEVEL_PROFILE,$category);
	}

	/**
	 * @return CLogger регистратор сообщений
	 */
	public static function getLogger()
	{
		if(self::$_logger!==null)
			return self::$_logger;
		else
			return self::$_logger=new CLogger;
	}

	/**
	 * Устанавливает объект регистратора сообщений
	 * @param CLogger $logger регистратор сообщений
	 * @since 1.1.8
	 */
	public static function setLogger($logger)
	{
		self::$_logger=$logger;
	}

	/**
	 * Возвращает строку, отображаемую на веб-странице и показывающую фразу о том, что сайт базируется на фреймворке Yii 
	 * @return string строка, отображаемая на веб-странице и показывающая фразу о том, что сайт базируется на фреймворке Yii 
	 */
	public static function powered()
	{
		return Yii::t('yii','Powered by {yii}.', array('{yii}'=>'<a href="http://www.yiiframework.com/" rel="external">Yii Framework</a>'));
	}

	/**
	 * Переводит сообщение на определенный язык.
	 * Данный метод поддерживает выбор формата (см. {@link CChoiceFormat}),
	 * т.е. возвращаемое сообщение будет выбрано среди нескольких согласно переданному числу.
	 * В основном данная функция используется для решения вопросов формата множественных чисел,
	 * если в языке сообщение имеет различный вид для различных чисел.
	 * @param string $category категория сообщения. Используйте только буквенные символы. Примечание: категория 'yii'
	 * зарезервирована для использования в коде ядра фреймворка Yii. Обратитесь к {@link CPhpMessageSource}
	 * за дополнительной информацией о категориях сообщений
	 * @param string $message оригинальное сообщение
	 * @param array $params параметры, применяемые к сообщению с использованием <code>strtr</code>.
	 * Первый параметр может быть числом без ключа.
	 * В этом случае метод будет вызывать метод {@link CChoiceFormat::format} для выбора
	 * соответствующего перевода. Начиная с версии 1.1.6 вы можете передать параметр для метода {@link CChoiceFormat::format}
	 * или формат плюральных форм без включения их в массив
	 * @param string $source какой компонент приложения использовать в качестве источника сообщений.
	 * По умолчанию - null, т.е. использовать 'coreMessages' для сообщений, принадлежащих
	 * к категории 'yii', и 'messages' - для остальных собщений
	 * @param string $language целевой язык. Если null (по умолчанию), то будет
	 * использоваться {@link CApplication::getLanguage язык приложения}
	 * @return string переведенное сообщение
	 * @see CMessageSource
	 */
	public static function t($category,$message,$params=array(),$source=null,$language=null)
	{
		if(self::$_app!==null)
		{
			if($source===null)
				$source=($category==='yii'||$category==='zii')?'coreMessages':'messages';
			if(($source=self::$_app->getComponent($source))!==null)
				$message=$source->translate($category,$message,$language);
		}
		if($params===array())
			return $message;
		if(!is_array($params))
			$params=array($params);
		if(isset($params[0])) // number choice
		{
			if(strpos($message,'|')!==false)
			{
				if(strpos($message,'#')===false)
				{
					$chunks=explode('|',$message);
					$expressions=self::$_app->getLocale($language)->getPluralRules();
					if($n=min(count($chunks),count($expressions)))
					{
						for($i=0;$i<$n;$i++)
							$chunks[$i]=$expressions[$i].'#'.$chunks[$i];

						$message=implode('|',$chunks);
					}
				}
				$message=CChoiceFormat::format($message,$params[0]);
			}
			if(!isset($params['{n}']))
				$params['{n}']=$params[0];
			unset($params[0]);
		}
		return $params!==array() ? strtr($message,$params) : $message;
	}

	/**
	 * Регистрирует новый автозагрузчик классов.
	 * Новый автозагрузчик будет помещен перед автозагрузчиком {@link autoload} и после
	 * других существующих автозагрузчиков.
	 * @param callback $callback правильный обратный вызов PHP (имя функции или массив вида array($className,$methodName)).
	 * @param boolean $append добавлять ли новый автоподгрузчик классов после встроенного в Yii автоподгрузчика
	 */
	public static function registerAutoloader($callback, $append=false)
	{
		if($append)
		{
			self::$enableIncludePath=false;
			spl_autoload_register($callback);
		}
		else
		{
			spl_autoload_unregister(array('YiiBase','autoload'));
			spl_autoload_register($callback);
			spl_autoload_register(array('YiiBase','autoload'));
		}
	}

	/**
	 * @var array класс-карта для классов ядра Yii.
	 * ПРИМЕЧАНИЕ: НЕ ИЗМЕНЯЙТЕ ДАННЫЙ МАССИВ ВРУЧНУЮ. ЕСЛИ ВЫ ИЗМЕНИТЕ ИЛИ ДОБАВИТЕ НЕКОТОРЫЕ КЛАССЫ ЯДРА,
	 * ЗАПУСТИТЕ КОМАНДУ 'build autoload' ДЛЯ ОБНОВЛЕНИЯ ДАННОГО МАССИВА.
	 */
	private static $_coreClasses=array(
		'CApplication' => '/base/CApplication.php',
		'CApplicationComponent' => '/base/CApplicationComponent.php',
		'CBehavior' => '/base/CBehavior.php',
		'CComponent' => '/base/CComponent.php',
		'CErrorEvent' => '/base/CErrorEvent.php',
		'CErrorHandler' => '/base/CErrorHandler.php',
		'CException' => '/base/CException.php',
		'CExceptionEvent' => '/base/CExceptionEvent.php',
		'CHttpException' => '/base/CHttpException.php',
		'CModel' => '/base/CModel.php',
		'CModelBehavior' => '/base/CModelBehavior.php',
		'CModelEvent' => '/base/CModelEvent.php',
		'CModule' => '/base/CModule.php',
		'CSecurityManager' => '/base/CSecurityManager.php',
		'CStatePersister' => '/base/CStatePersister.php',
		'CApcCache' => '/caching/CApcCache.php',
		'CCache' => '/caching/CCache.php',
		'CDbCache' => '/caching/CDbCache.php',
		'CDummyCache' => '/caching/CDummyCache.php',
		'CEAcceleratorCache' => '/caching/CEAcceleratorCache.php',
		'CFileCache' => '/caching/CFileCache.php',
		'CMemCache' => '/caching/CMemCache.php',
		'CWinCache' => '/caching/CWinCache.php',
		'CXCache' => '/caching/CXCache.php',
		'CZendDataCache' => '/caching/CZendDataCache.php',
		'CCacheDependency' => '/caching/dependencies/CCacheDependency.php',
		'CChainedCacheDependency' => '/caching/dependencies/CChainedCacheDependency.php',
		'CDbCacheDependency' => '/caching/dependencies/CDbCacheDependency.php',
		'CDirectoryCacheDependency' => '/caching/dependencies/CDirectoryCacheDependency.php',
		'CExpressionDependency' => '/caching/dependencies/CExpressionDependency.php',
		'CFileCacheDependency' => '/caching/dependencies/CFileCacheDependency.php',
		'CGlobalStateCacheDependency' => '/caching/dependencies/CGlobalStateCacheDependency.php',
		'CAttributeCollection' => '/collections/CAttributeCollection.php',
		'CConfiguration' => '/collections/CConfiguration.php',
		'CList' => '/collections/CList.php',
		'CListIterator' => '/collections/CListIterator.php',
		'CMap' => '/collections/CMap.php',
		'CMapIterator' => '/collections/CMapIterator.php',
		'CQueue' => '/collections/CQueue.php',
		'CQueueIterator' => '/collections/CQueueIterator.php',
		'CStack' => '/collections/CStack.php',
		'CStackIterator' => '/collections/CStackIterator.php',
		'CTypedList' => '/collections/CTypedList.php',
		'CTypedMap' => '/collections/CTypedMap.php',
		'CConsoleApplication' => '/console/CConsoleApplication.php',
		'CConsoleCommand' => '/console/CConsoleCommand.php',
		'CConsoleCommandRunner' => '/console/CConsoleCommandRunner.php',
		'CConsoleCommandEvent' => '/console/CConsoleCommandEvent.php',
		'CConsoleCommandBehavior' => '/console/CConsoleCommandBehavior.php',
		'CHelpCommand' => '/console/CHelpCommand.php',
		'CDbCommand' => '/db/CDbCommand.php',
		'CDbConnection' => '/db/CDbConnection.php',
		'CDbDataReader' => '/db/CDbDataReader.php',
		'CDbException' => '/db/CDbException.php',
		'CDbMigration' => '/db/CDbMigration.php',
		'CDbTransaction' => '/db/CDbTransaction.php',
		'CActiveFinder' => '/db/ar/CActiveFinder.php',
		'CActiveRecord' => '/db/ar/CActiveRecord.php',
		'CActiveRecordBehavior' => '/db/ar/CActiveRecordBehavior.php',
		'CDbColumnSchema' => '/db/schema/CDbColumnSchema.php',
		'CDbCommandBuilder' => '/db/schema/CDbCommandBuilder.php',
		'CDbCriteria' => '/db/schema/CDbCriteria.php',
		'CDbExpression' => '/db/schema/CDbExpression.php',
		'CDbSchema' => '/db/schema/CDbSchema.php',
		'CDbTableSchema' => '/db/schema/CDbTableSchema.php',
		'CMssqlColumnSchema' => '/db/schema/mssql/CMssqlColumnSchema.php',
		'CMssqlCommandBuilder' => '/db/schema/mssql/CMssqlCommandBuilder.php',
		'CMssqlPdoAdapter' => '/db/schema/mssql/CMssqlPdoAdapter.php',
		'CMssqlSchema' => '/db/schema/mssql/CMssqlSchema.php',
		'CMssqlTableSchema' => '/db/schema/mssql/CMssqlTableSchema.php',
		'CMysqlColumnSchema' => '/db/schema/mysql/CMysqlColumnSchema.php',
		'CMysqlSchema' => '/db/schema/mysql/CMysqlSchema.php',
		'CMysqlTableSchema' => '/db/schema/mysql/CMysqlTableSchema.php',
		'COciColumnSchema' => '/db/schema/oci/COciColumnSchema.php',
		'COciCommandBuilder' => '/db/schema/oci/COciCommandBuilder.php',
		'COciSchema' => '/db/schema/oci/COciSchema.php',
		'COciTableSchema' => '/db/schema/oci/COciTableSchema.php',
		'CPgsqlColumnSchema' => '/db/schema/pgsql/CPgsqlColumnSchema.php',
		'CPgsqlSchema' => '/db/schema/pgsql/CPgsqlSchema.php',
		'CPgsqlTableSchema' => '/db/schema/pgsql/CPgsqlTableSchema.php',
		'CSqliteColumnSchema' => '/db/schema/sqlite/CSqliteColumnSchema.php',
		'CSqliteCommandBuilder' => '/db/schema/sqlite/CSqliteCommandBuilder.php',
		'CSqliteSchema' => '/db/schema/sqlite/CSqliteSchema.php',
		'CChoiceFormat' => '/i18n/CChoiceFormat.php',
		'CDateFormatter' => '/i18n/CDateFormatter.php',
		'CDbMessageSource' => '/i18n/CDbMessageSource.php',
		'CGettextMessageSource' => '/i18n/CGettextMessageSource.php',
		'CLocale' => '/i18n/CLocale.php',
		'CMessageSource' => '/i18n/CMessageSource.php',
		'CNumberFormatter' => '/i18n/CNumberFormatter.php',
		'CPhpMessageSource' => '/i18n/CPhpMessageSource.php',
		'CGettextFile' => '/i18n/gettext/CGettextFile.php',
		'CGettextMoFile' => '/i18n/gettext/CGettextMoFile.php',
		'CGettextPoFile' => '/i18n/gettext/CGettextPoFile.php',
		'CDbLogRoute' => '/logging/CDbLogRoute.php',
		'CEmailLogRoute' => '/logging/CEmailLogRoute.php',
		'CFileLogRoute' => '/logging/CFileLogRoute.php',
		'CLogFilter' => '/logging/CLogFilter.php',
		'CLogRoute' => '/logging/CLogRoute.php',
		'CLogRouter' => '/logging/CLogRouter.php',
		'CLogger' => '/logging/CLogger.php',
		'CProfileLogRoute' => '/logging/CProfileLogRoute.php',
		'CWebLogRoute' => '/logging/CWebLogRoute.php',
		'CDateTimeParser' => '/utils/CDateTimeParser.php',
		'CFileHelper' => '/utils/CFileHelper.php',
		'CFormatter' => '/utils/CFormatter.php',
		'CMarkdownParser' => '/utils/CMarkdownParser.php',
		'CPropertyValue' => '/utils/CPropertyValue.php',
		'CTimestamp' => '/utils/CTimestamp.php',
		'CVarDumper' => '/utils/CVarDumper.php',
		'CBooleanValidator' => '/validators/CBooleanValidator.php',
		'CCaptchaValidator' => '/validators/CCaptchaValidator.php',
		'CCompareValidator' => '/validators/CCompareValidator.php',
		'CDateValidator' => '/validators/CDateValidator.php',
		'CDefaultValueValidator' => '/validators/CDefaultValueValidator.php',
		'CEmailValidator' => '/validators/CEmailValidator.php',
		'CExistValidator' => '/validators/CExistValidator.php',
		'CFileValidator' => '/validators/CFileValidator.php',
		'CFilterValidator' => '/validators/CFilterValidator.php',
		'CInlineValidator' => '/validators/CInlineValidator.php',
		'CNumberValidator' => '/validators/CNumberValidator.php',
		'CRangeValidator' => '/validators/CRangeValidator.php',
		'CRegularExpressionValidator' => '/validators/CRegularExpressionValidator.php',
		'CRequiredValidator' => '/validators/CRequiredValidator.php',
		'CSafeValidator' => '/validators/CSafeValidator.php',
		'CStringValidator' => '/validators/CStringValidator.php',
		'CTypeValidator' => '/validators/CTypeValidator.php',
		'CUniqueValidator' => '/validators/CUniqueValidator.php',
		'CUnsafeValidator' => '/validators/CUnsafeValidator.php',
		'CUrlValidator' => '/validators/CUrlValidator.php',
		'CValidator' => '/validators/CValidator.php',
		'CActiveDataProvider' => '/web/CActiveDataProvider.php',
		'CArrayDataProvider' => '/web/CArrayDataProvider.php',
		'CAssetManager' => '/web/CAssetManager.php',
		'CBaseController' => '/web/CBaseController.php',
		'CCacheHttpSession' => '/web/CCacheHttpSession.php',
		'CClientScript' => '/web/CClientScript.php',
		'CController' => '/web/CController.php',
		'CDataProvider' => '/web/CDataProvider.php',
		'CDbHttpSession' => '/web/CDbHttpSession.php',
		'CExtController' => '/web/CExtController.php',
		'CFormModel' => '/web/CFormModel.php',
		'CHttpCookie' => '/web/CHttpCookie.php',
		'CHttpRequest' => '/web/CHttpRequest.php',
		'CHttpSession' => '/web/CHttpSession.php',
		'CHttpSessionIterator' => '/web/CHttpSessionIterator.php',
		'COutputEvent' => '/web/COutputEvent.php',
		'CPagination' => '/web/CPagination.php',
		'CSort' => '/web/CSort.php',
		'CSqlDataProvider' => '/web/CSqlDataProvider.php',
		'CTheme' => '/web/CTheme.php',
		'CThemeManager' => '/web/CThemeManager.php',
		'CUploadedFile' => '/web/CUploadedFile.php',
		'CUrlManager' => '/web/CUrlManager.php',
		'CWebApplication' => '/web/CWebApplication.php',
		'CWebModule' => '/web/CWebModule.php',
		'CWidgetFactory' => '/web/CWidgetFactory.php',
		'CAction' => '/web/actions/CAction.php',
		'CInlineAction' => '/web/actions/CInlineAction.php',
		'CViewAction' => '/web/actions/CViewAction.php',
		'CAccessControlFilter' => '/web/auth/CAccessControlFilter.php',
		'CAuthAssignment' => '/web/auth/CAuthAssignment.php',
		'CAuthItem' => '/web/auth/CAuthItem.php',
		'CAuthManager' => '/web/auth/CAuthManager.php',
		'CBaseUserIdentity' => '/web/auth/CBaseUserIdentity.php',
		'CDbAuthManager' => '/web/auth/CDbAuthManager.php',
		'CPhpAuthManager' => '/web/auth/CPhpAuthManager.php',
		'CUserIdentity' => '/web/auth/CUserIdentity.php',
		'CWebUser' => '/web/auth/CWebUser.php',
		'CFilter' => '/web/filters/CFilter.php',
		'CFilterChain' => '/web/filters/CFilterChain.php',
		'CInlineFilter' => '/web/filters/CInlineFilter.php',
		'CForm' => '/web/form/CForm.php',
		'CFormButtonElement' => '/web/form/CFormButtonElement.php',
		'CFormElement' => '/web/form/CFormElement.php',
		'CFormElementCollection' => '/web/form/CFormElementCollection.php',
		'CFormInputElement' => '/web/form/CFormInputElement.php',
		'CFormStringElement' => '/web/form/CFormStringElement.php',
		'CGoogleApi' => '/web/helpers/CGoogleApi.php',
		'CHtml' => '/web/helpers/CHtml.php',
		'CJSON' => '/web/helpers/CJSON.php',
		'CJavaScript' => '/web/helpers/CJavaScript.php',
		'CPradoViewRenderer' => '/web/renderers/CPradoViewRenderer.php',
		'CViewRenderer' => '/web/renderers/CViewRenderer.php',
		'CWebService' => '/web/services/CWebService.php',
		'CWebServiceAction' => '/web/services/CWebServiceAction.php',
		'CWsdlGenerator' => '/web/services/CWsdlGenerator.php',
		'CActiveForm' => '/web/widgets/CActiveForm.php',
		'CAutoComplete' => '/web/widgets/CAutoComplete.php',
		'CClipWidget' => '/web/widgets/CClipWidget.php',
		'CContentDecorator' => '/web/widgets/CContentDecorator.php',
		'CFilterWidget' => '/web/widgets/CFilterWidget.php',
		'CFlexWidget' => '/web/widgets/CFlexWidget.php',
		'CHtmlPurifier' => '/web/widgets/CHtmlPurifier.php',
		'CInputWidget' => '/web/widgets/CInputWidget.php',
		'CMarkdown' => '/web/widgets/CMarkdown.php',
		'CMaskedTextField' => '/web/widgets/CMaskedTextField.php',
		'CMultiFileUpload' => '/web/widgets/CMultiFileUpload.php',
		'COutputCache' => '/web/widgets/COutputCache.php',
		'COutputProcessor' => '/web/widgets/COutputProcessor.php',
		'CStarRating' => '/web/widgets/CStarRating.php',
		'CTabView' => '/web/widgets/CTabView.php',
		'CTextHighlighter' => '/web/widgets/CTextHighlighter.php',
		'CTreeView' => '/web/widgets/CTreeView.php',
		'CWidget' => '/web/widgets/CWidget.php',
		'CCaptcha' => '/web/widgets/captcha/CCaptcha.php',
		'CCaptchaAction' => '/web/widgets/captcha/CCaptchaAction.php',
		'CBasePager' => '/web/widgets/pagers/CBasePager.php',
		'CLinkPager' => '/web/widgets/pagers/CLinkPager.php',
		'CListPager' => '/web/widgets/pagers/CListPager.php',
	);
}

spl_autoload_register(array('YiiBase','autoload'));
require(YII_PATH.'/base/interfaces.php');
