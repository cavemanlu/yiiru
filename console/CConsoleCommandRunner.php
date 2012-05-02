<?php
/**
 * Файл класса CConsoleCommandRunner.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * CConsoleCommandRunner управляет командами и вполняет запрошенную команду.
 *
 * @property string $scriptName имя скрипта точки входа
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CConsoleCommandRunner.php 3426 2011-10-25 00:01:09Z alexander.makarow $
 * @package system.console
 * @since 1.0
 */
class CConsoleCommandRunner extends CComponent
{
	/**
	 * @var array список всех доступных команд (имя команды => настройки команды).
	 * Настройки каждой команды могут быть в виде строки либо массива.
	 * Если это строка, то она должна быть именем класса или {@link YiiBase::getPathOfAlias псевдонимом} команды.
	 * Если массив, то он должен содержать элемент с ключом 'class', определяющим имя класса команды
	 * или его {@link YiiBase::getPathOfAlias псевдонима}.
	 * Остальные пары массива "имя-значение" используются для инициализации
	 * соответствующих свойств команды. Например,
	 * <pre>
	 * array(
	 *   'email'=>array(
	 *      'class'=>'path.to.Mailer',
	 *      'interval'=>3600,
	 *   ),
	 *   'log'=>'path.to.LoggerCommand',
	 * )
	 * </pre>
	 */
	public $commands=array();

	private $_scriptName;

	/**
	 * Выполняет запрошенную команду.
	 * @param array $args список введенных пользователем параметров (включая имя скрипта точки входа имя команды)
	 */
	public function run($args)
	{
		$this->_scriptName=$args[0];
		array_shift($args);
		if(isset($args[0]))
		{
			$name=$args[0];
			array_shift($args);
		}
		else
			$name='help';

		if(($command=$this->createCommand($name))===null)
			$command=$this->createCommand('help');
		$command->init();
		$command->run($args);
	}

	/**
	 * Возвращает имя скрипта точки входа.
	 * @return string имя скрипта точки входа
	 */
	public function getScriptName()
	{
		return $this->_scriptName;
	}

	/**
	 * Ищет команды в определенной директории.
	 * @param string $path директория, содержащая файлы классов команд
	 * @return array список команд (имя команды => файл класса команды)
	 */
	public function findCommands($path)
	{
		if(($dir=@opendir($path))===false)
			return array();
		$commands=array();
		while(($name=readdir($dir))!==false)
		{
			$file=$path.DIRECTORY_SEPARATOR.$name;
			if(!strcasecmp(substr($name,-11),'Command.php') && is_file($file))
				$commands[strtolower(substr($name,0,-11))]=$file;
		}
		closedir($dir);
		return $commands;
	}

	/**
	 * Добавляет команды по определенному командному пути.
	 * Если команда уже существует, новая будет проигнорирована.
	 * @param string $path псевдоним директории, содержащей файлы классов команд
	 */
	public function addCommands($path)
	{
		if(($commands=$this->findCommands($path))!==array())
		{
			foreach($commands as $name=>$file)
			{
				if(!isset($this->commands[$name]))
					$this->commands[$name]=$file;
			}
		}
	}

	/**
	 * Создает объект команды по ее имени.
	 * @param string $name имя команды (регистронезависимо)
	 * @return CConsoleCommand объект команды. Если имя неверно, возвращается значение null
	 */
	public function createCommand($name)
	{
		$name=strtolower($name);
		if(isset($this->commands[$name]))
		{
			if(is_string($this->commands[$name]))  // class file path or alias
			{
				if(strpos($this->commands[$name],'/')!==false || strpos($this->commands[$name],'\\')!==false)
				{
					$className=substr(basename($this->commands[$name]),0,-4);
					if(!class_exists($className,false))
						require_once($this->commands[$name]);
				}
				else // an alias
					$className=Yii::import($this->commands[$name]);
				return new $className($name,$this);
			}
			else // an array configuration
				return Yii::createComponent($this->commands[$name],$name,$this);
		}
		else if($name==='help')
			return new CHelpCommand('help',$this);
		else
			return null;
	}
}