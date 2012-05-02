<?php
/**
 * Файл класса CConsoleCommand.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Класс CConsoleCommand представляет выполняемую консольную команду.
 *
 * Он работает как {@link CController} разбирая аргументы командной строки и отправляя запрос
 * определенному действию с соответствующими значениями параметров.
 *
 * Пользователи вызывают консольную команду в следущем формате команды:
 * <pre>
 * yiic ИмяКоманды ИмяДействия --Параметр1=Значение1 --Параметр2=Значение2 ...
 * </pre>
 *
 * Классам-потомкам главным образом надо реализовать различные методы действий, имена которых
 * должны начинаться с "action". Параметры метода действия считаются опциями определенного действия.
 * Действие, определенное как {@link defaultAction} будет вызываться в случае, если
 * пользователь не определил имя действия в команде.
 *
 * Опции связаны с параметрами действия именами параметров. Например, следующий метод действия
 * позволит нам запустить команду <code>yiic sitemap --type=News</code>:
 * <pre>
 * class SitemapCommand {
 *     public function actionIndex($type) {
 *         ....
 *     }
 * }
 * </pre>
 *
 * @property string $name имя команды
 * @property CConsoleCommandRunner $commandRunner экземпляр исполнителя
 * (runner) команды
 * @property string $help описание команды. По умолчанию выводится строка
 * 'Usage: php файл-скрипта.php имя-команды'
 * @property array $optionHelp вспомогательная информация по опциям команды.
 * Каждый эдемент массива описывает вспомогательную информацию для отдельного
 * действия
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CConsoleCommand.php 3598 2012-02-19 21:22:09Z qiang.xue@gmail.com $
 * @package system.console
 * @since 1.0
 */
abstract class CConsoleCommand extends CComponent
{
	/**
	 * @var string название действия по умолчанию. По умолчанию - 'index'
	 * @since 1.1.5
	 */
	public $defaultAction='index';

	private $_name;
	private $_runner;

	/**
	 * Конструктор.
	 * @param string $name имя команды
	 * @param CConsoleCommandRunner $runner исполнитель (runner) команды
	 */
	public function __construct($name,$runner)
	{
		$this->_name=$name;
		$this->_runner=$runner;
		$this->attachBehaviors($this->behaviors());
	}

	/**
	 * Инициализирует объект команды.
	 * Данный метод вызывается после создания и инициализации объекта команды.
	 * Вы можете переопределить данный метод для дальнейшей настройки команды перед ее выполнением
	 * @since 1.1.6
	 */
	public function init()
	{
	}
	
	/**
	 * Возвращает список поведений, свойства которых должна перенимать команда.
	 * Возвращаемое значение должно быть массивом конфигураций поведений,
	 * индексированным по именам поведений. Каждая конфигурация поведения может
	 * быть либо строкой, определяющей класс поведения, либо массивом со
	 * следующей структурой:
	 * <pre>
	 * 'behaviorName'=>array(
	 *     'class'=>'path.to.BehaviorClass',
	 *     'property1'=>'value1',
	 *     'property2'=>'value2',
	 * )
	 * </pre>
	 *
	 * Примечание: классы поведений должны реализовывать интерфейс
	 * {@link IBehavior} или наследовать класс {@link CBehavior}. Поведения,
	 * объявленные в данном методе, будут присоединены к контроллеру при
	 * создании экземпляра контроллера.
	 *
	 * За деталями о поведениях обратитесь к классу {@link CComponent}
	 * @return array конфигурации поведений (имя поведения => конфигурация
	 * поведения)
	 * @since 1.1.11
	 */
	public function behaviors()
	{
		return array();
	}

	/**
	 * Запускает команду.
	 * Реализация по умолчанию определяет входные параметры и отправляет запрос
	 * команды в подходящее действие с соответствующими значениями параметров
	 * @param array $args параметры командной строки для данной команды
	 */
	public function run($args)
	{
		list($action, $options, $args)=$this->resolveRequest($args);
		$methodName='action'.$action;
		if(!preg_match('/^\w+$/',$action) || !method_exists($this,$methodName))
			$this->usageError("Unknown action: ".$action);

		$method=new ReflectionMethod($this,$methodName);
		$params=array();
		// named and unnamed options
		foreach($method->getParameters() as $i=>$param)
		{
			$name=$param->getName();
			if(isset($options[$name]))
			{
				if($param->isArray())
					$params[]=is_array($options[$name]) ? $options[$name] : array($options[$name]);
				else if(!is_array($options[$name]))
					$params[]=$options[$name];
				else
					$this->usageError("Option --$name requires a scalar. Array is given.");
			}
			else if($name==='args')
				$params[]=$args;
			else if($param->isDefaultValueAvailable())
				$params[]=$param->getDefaultValue();
			else
				$this->usageError("Missing required option --$name.");
			unset($options[$name]);
		}

		// try global options
		if(!empty($options))
		{
			$class=new ReflectionClass(get_class($this));
			foreach($options as $name=>$value)
			{
				if($class->hasProperty($name))
				{
					$property=$class->getProperty($name);
					if($property->isPublic() && !$property->isStatic())
					{
						$this->$name=$value;
						unset($options[$name]);
					}
				}
			}
		}

		if(!empty($options))
			$this->usageError("Unknown options: ".implode(', ',array_keys($options)));

		if($this->beforeAction($action,$params))
		{
			$method->invokeArgs($this,$params);
			$this->afterAction($action,$params);
		}
	}

	/**
	 * Данный метод вызывается непосредственно перед выполняемым действием.
	 * Вы можете переопределить данный метод для выполнения последней подготовки для действия
	 * @param string $action имя действия
	 * @param array $params параметры, передаваемые в метод действия
	 * @return boolean должно ли действие выполниться
	 */
	protected function beforeAction($action,$params)
	{
		if($this->hasEventHandler('onBeforeAction'))
		{
			$event = new CConsoleCommandEvent($this, $params, $action);
			$this->onBeforeAction($event);
			return !$event->stopCommand;
		}
		else
		{
			return true;
		}
	}

	/**
	 * Данный метод выполняется сразу после окончания выполнения действия.
	 * Вы можете переопределить данный метод для выполнения некоторых постопераций для действия
	 * @param string $action имя действия
	 * @param array $params параметры, передаваемые в метод действия
	 */
	protected function afterAction($action,$params)
	{
		if($this->hasEventHandler('onAfterAction'))
			$this->onAfterAction(new CConsoleCommandEvent($this, $params, $action));
	}

	/**
	 * Разбирает аргументы командной строки и определяет выполняемое действие
	 * @param array $args аргументы командной строки
	 * @return array имя действия, именованные опции (имя=>значение) и неименованные  опции
	 * @since 1.1.5
	 */
	protected function resolveRequest($args)
	{
		$options=array();	// named parameters
		$params=array();	// unnamed parameters
		foreach($args as $arg)
		{
			if(preg_match('/^--(\w+)(=(.*))?$/',$arg,$matches))  // an option
			{
				$name=$matches[1];
				$value=isset($matches[3]) ? $matches[3] : true;
				if(isset($options[$name]))
				{
					if(!is_array($options[$name]))
						$options[$name]=array($options[$name]);
					$options[$name][]=$value;
				}
				else
					$options[$name]=$value;
			}
			else if(isset($action))
				$params[]=$arg;
			else
				$action=$arg;
		}
		if(!isset($action))
			$action=$this->defaultAction;

		return array($action,$options,$params);
	}

	/**
	 * @return string имя команды
	 */
	public function getName()
	{
		return $this->_name;
	}

	/**
	 * @return CConsoleCommandRunner экземпляр исполнителя (runner) команды
	 */
	public function getCommandRunner()
	{
		return $this->_runner;
	}

	/**
	 * Предоставляет описание команды.
	 * Метод может быть переопределен для вывода расширенного описания команды.
	 * @return string описание команды. По умолчанию выводится строка 'Usage: php файл-скрипта.php имя-команды'
	 */
	public function getHelp()
	{
		$help='Usage: '.$this->getCommandRunner()->getScriptName().' '.$this->getName();
		$options=$this->getOptionHelp();
		if(empty($options))
			return $help;
		if(count($options)===1)
			return $help.' '.$options[0];
		$help.=" <action>\nActions:\n";
		foreach($options as $option)
			$help.='    '.$option."\n";
		return $help;
	}

	/**
	 * Предоставляет вспомогательную информацию по опциям команды.
	 * Реализация по умолчанию будет возвращать все доступные действия вместе
	 * с информацией об их соответствующих параметрах
	 * @return array вспомогательная информация по опциям команды. Каждый эдемент массива описывает
	 * вспомогательную информацию для отдельного действия
	 * @since 1.1.5
	 */
	public function getOptionHelp()
	{
		$options=array();
		$class=new ReflectionClass(get_class($this));
        foreach($class->getMethods(ReflectionMethod::IS_PUBLIC) as $method)
        {
        	$name=$method->getName();
        	if(!strncasecmp($name,'action',6) && strlen($name)>6)
        	{
        		$name=substr($name,6);
        		$name[0]=strtolower($name[0]);
        		$help=$name;

				foreach($method->getParameters() as $param)
				{
					$optional=$param->isDefaultValueAvailable();
					$defaultValue=$optional ? $param->getDefaultValue() : null;
					$name=$param->getName();
					if($optional)
						$help.=" [--$name=$defaultValue]";
					else
						$help.=" --$name=value";
				}
				$options[]=$help;
        	}
        }
        return $options;
	}

	/**
	 * Отображает ошибки использования.
	 * Метод прерывает выполнение текущего приложения.
	 * @param string $message сообщение ошибки
	 */
	public function usageError($message)
	{
		echo "Error: $message\n\n".$this->getHelp()."\n";
		exit(1);
	}

	/**
	 * Копирует список файлов из одного места в другое.
	 * @param array $fileList список копируемых файлов (имя => параметры).
	 * Ключи массива - имена, отображаемые во время процесса копирования, а его значения - параметры
	 * копируемых файлов. Каждое значение массива должно быть массивом следующей структуры:
	 * <ul>
	 * <li>source: обязательно, полный путь копируемого файла/директории;</li>
	 * <li>target: обязательно, полный путь до места назначения;</li>
	 * <li>callback: опционально, обратный вызов, выполняемый при копировании файла. Функция обратного вызова
	 *   должна определяться так:
	 *   <pre>
	 *   function foo($source,$params)
	 *   </pre>
	 *   где параметр $source - исходный путь до файла, возвращаемые функцией данные
	 *   будут сохранены в целевой файл;</li>
	 * <li>params: опционально, параметры, передаваемые в обратный вызов</li>
	 * </ul>
	 * @see buildFileList
	 */
	public function copyFiles($fileList)
	{
		$overwriteAll=false;
		foreach($fileList as $name=>$file)
		{
			$source=strtr($file['source'],'/\\',DIRECTORY_SEPARATOR);
			$target=strtr($file['target'],'/\\',DIRECTORY_SEPARATOR);
			$callback=isset($file['callback']) ? $file['callback'] : null;
			$params=isset($file['params']) ? $file['params'] : null;

			if(is_dir($source))
			{
				$this->ensureDirectory($target);
				continue;
			}

			if($callback!==null)
				$content=call_user_func($callback,$source,$params);
			else
				$content=file_get_contents($source);
			if(is_file($target))
			{
				if($content===file_get_contents($target))
				{
					echo "  unchanged $name\n";
					continue;
				}
				if($overwriteAll)
					echo "  overwrite $name\n";
				else
				{
					echo "      exist $name\n";
					echo "            ...overwrite? [Yes|No|All|Quit] ";
					$answer=trim(fgets(STDIN));
					if(!strncasecmp($answer,'q',1))
						return;
					else if(!strncasecmp($answer,'y',1))
						echo "  overwrite $name\n";
					else if(!strncasecmp($answer,'a',1))
					{
						echo "  overwrite $name\n";
						$overwriteAll=true;
					}
					else
					{
						echo "       skip $name\n";
						continue;
					}
				}
			}
			else
			{
				$this->ensureDirectory(dirname($target));
				echo "   generate $name\n";
			}
			file_put_contents($target,$content);
		}
	}

	/**
	 * Строит список файлов в директории.
	 * Метод просматривает переданную в параметре директорию и строит список файлов
	 * и поддиректорий, содержащихся в данной директории.
	 * Результат данной функции может быть передан в метод {@link copyFiles}.
	 * @param string $sourceDir исходная директория
	 * @param string $targetDir целевая директория
	 * @param string $baseDir базовая директория
	 * @return array список файлов (см. {@link copyFiles})
	 */
	public function buildFileList($sourceDir, $targetDir, $baseDir='')
	{
		$list=array();
		$handle=opendir($sourceDir);
		while(($file=readdir($handle))!==false)
		{
			if($file==='.' || $file==='..' || $file==='.svn' ||$file==='.gitignore')
				continue;
			$sourcePath=$sourceDir.DIRECTORY_SEPARATOR.$file;
			$targetPath=$targetDir.DIRECTORY_SEPARATOR.$file;
			$name=$baseDir===''?$file : $baseDir.'/'.$file;
			$list[$name]=array('source'=>$sourcePath, 'target'=>$targetPath);
			if(is_dir($sourcePath))
				$list=array_merge($list,$this->buildFileList($sourcePath,$targetPath,$name));
		}
		closedir($handle);
		return $list;
	}

	/**
	 * Создает все родительские директории, если они не существуют
	 * @param string $directory проверяемая директория
	 */
	public function ensureDirectory($directory)
	{
		if(!is_dir($directory))
		{
			$this->ensureDirectory(dirname($directory));
			echo "      mkdir ".strtr($directory,'\\','/')."\n";
			mkdir($directory);
		}
	}

	/**
	 * Рендерит файл представления.
	 * @param string $_viewFile_ путь до файла представления
	 * @param array $_data_ опциональные данные, распаковываемые в виде локальных переменных представления
	 * @param boolean $_return_ возвратить ли результат рендера вместо его отображения на экран
	 * @return mixed результат рендера по требованию, иначе null
	 */
	public function renderFile($_viewFile_,$_data_=null,$_return_=false)
	{
		if(is_array($_data_))
			extract($_data_,EXTR_PREFIX_SAME,'data');
		else
			$data=$_data_;
		if($_return_)
		{
			ob_start();
			ob_implicit_flush(false);
			require($_viewFile_);
			return ob_get_clean();
		}
		else
			require($_viewFile_);
	}

	/**
	 * Конвертирует слово во множественную форму (плюрализация). Только английские слова
	 * @param string $name плюрализуемое слово
	 * @return string плюрализованное слово
	 */
	public function pluralize($name)
	{
		$rules=array(
			'/move$/i' => 'moves',
			'/foot$/i' => 'feet',
			'/child$/i' => 'children',
			'/human$/i' => 'humans',
			'/man$/i' => 'men',
			'/tooth$/i' => 'teeth',
			'/person$/i' => 'people',
			'/([m|l])ouse$/i' => '\1ice',
			'/(x|ch|ss|sh|us|as|is|os)$/i' => '\1es',
			'/([^aeiouy]|qu)y$/i' => '\1ies',
			'/(?:([^f])fe|([lr])f)$/i' => '\1\2ves',
			'/(shea|lea|loa|thie)f$/i' => '\1ves',
			'/([ti])um$/i' => '\1a',
			'/(tomat|potat|ech|her|vet)o$/i' => '\1oes',
			'/(bu)s$/i' => '\1ses',
			'/(ax|test)is$/i' => '\1es',
			'/s$/' => 's',
		);
		foreach($rules as $rule=>$replacement)
		{
			if(preg_match($rule,$name))
				return preg_replace($rule,$replacement,$name);
		}
		return $name.'s';
	}

	/**
	 * Считывает введенные данные с помощью расширения readline для PHP, если
	 * оно доступно, или функцией fgets(), если расширение readline не
	 * установлено
	 *
	 * @param string $message выводимое сообщение при ожидании пользовательского ответа
	 * @return mixed считанная строка или false, если ввод данных был закрыт (?)
	 *
	 * @since 1.1.9
	 */
	public function prompt($message)
	{
		if(extension_loaded('readline'))
		{
			$input = readline($message.' ');
			readline_add_history($input);
			return $input;
		}
		else
		{
			echo $message.' ';
			return trim(fgets(STDIN));
		}
	}

	/**
	 * Запрашивает пользователя подтвержение выполнения с помощью букв "y" или
	 * "n" (выполнить или отменить соответственно)
	 *
	 * @param string $message выводимое сообщение при ожидании пользовательского ответа
	 * @return bool подтвердил ли пользователь выполнение
	 *
	 * @since 1.1.9
	 */
	public function confirm($message)
	{
		echo $message.' [yes|no] ';
		return !strncasecmp(trim(fgets(STDIN)),'y',1);
	}

	/**
	 * Данное событие вызывается перед выполнением действия
	 * @param CConsoleCommandEvent $event параметр события
	 * @since 1.1.11
	 */
	public function onBeforeAction($event)
	{
		$this->raiseEvent('onBeforeAction',$event);
	}

	/**
	 * Данное событие вызывается полсе окончания выполнения действия
	 * @param CConsoleCommandEvent $event параметр события
	 * @since 1.1.11
	 */
	public function onAfterAction($event)
	{
		$this->raiseEvent('onAfterAction',$event);
	}
}