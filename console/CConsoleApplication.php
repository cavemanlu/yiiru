<?php
/**
 * Файл класса CConsoleApplication.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Класс CConsoleApplication представляет собой консольное приложение.
 *
 * CConsoleApplication расширяет {@link CApplication} предоставляя специфичную
 * для консольных запросов функциональность. В частности, он позволяет создавать
 * консольные запросы в виде команд. При этом, должны соблюдаться условия:
 * <ul>
 * <li>Консольное приложение содержит одну или несколько возможных пользовательских команд;</li>
 * <li>Каждая пользовательская команда реализуется как наследник класса {@link CConsoleCommand};</li>
 * <li>Пользователь определяет в командной строке, какая команда должна выполниться;</li>
 * <li>Команда выполняет пользовательсякий запрос с определенными параметрами.</li>
 * </ul>
 *
 * Классы команд располагаются в директории, задаваемой свойством {@link getCommandPath commandPath}.
 * Имя класса соответствует виду &lt;имя-команды&gt;Command. Имя файла класса идентично
 * имени класса. Например, класс 'ShellCommand' определяет команду
 * 'shell' и находится в файле 'ShellCommand.php'.
 *
 * Для запуска консольного приложения, введите следующую командную строку:
 * <pre>
 * php путь/к/скрипту-точке-входа.php <имя-команды> [param 1] [param 2] ...
 * </pre>
 *
 * Вы можете использовать следующую команду для просмотра информации о команде:
 * <pre>
 * php путь/к/скрипту-точке-входа.php help <имя-команды>
 * </pre>
 *
 * @property string $commandPath директория, содержащая классы команды. По
 * умолчанию 'protected/commands'
 * @property CConsoleCommandRunner $commandRunner экземпляр исполнителя
 * (runner) команд
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CConsoleApplication.php 3426 2011-10-25 00:01:09Z alexander.makarow $
 * @package system.console
 * @since 1.0
 */
class CConsoleApplication extends CApplication
{
	/**
	 * @var array массив соответствий (карта) имен команд и конфигураций команд.
	 * Каждая конфигурации команды может быть строкой или массивом.
	 * Если это строка, то она должна содержать путь к файлу класса команды.
	 * Если массив - он должен содержать элемент с ключом 'class', определяющим имя класса команды
	 * или {@link YiiBase::getPathOfAlias псевдоним класса}.
	 * Остальные пары имя-значения массива используются для инициализации
	 * соответствующих свойств команды. Например,
	 * <pre>
	 * array(
	 *   'email'=>array(
	 *      'class'=>'path.to.Mailer',
	 *      'interval'=>3600,
	 *   ),
	 *   'log'=>'path/to/LoggerCommand.php',
	 * )
	 * </pre>
	 */
	public $commandMap=array();

	private $_commandPath;
	private $_runner;

	/**
	 * Инициализирует приложение созданием исполнителя (runner) команд.
	 */
	protected function init()
	{
		parent::init();
		if(!isset($_SERVER['argv'])) // || strncasecmp(php_sapi_name(),'cli',3))
			die('This script must be run from the command line.');
		$this->_runner=$this->createCommandRunner();
		$this->_runner->commands=$this->commandMap;
		$this->_runner->addCommands($this->getCommandPath());
	}

	/**
	 * Выполняет запрос пользователя.
	 * Метод создает экземпляр консольного исполнителя (runner) команд для обработки частной пользовательской команды.
	 */
	public function processRequest()
	{
		$this->_runner->run($_SERVER['argv']);
	}

	/**
	 * Создает экземпляр исполнителя (runner) команд.
	 * @return CConsoleCommandRunner экземпляр исполнителя (runner) команд
	 */
	protected function createCommandRunner()
	{
		return new CConsoleCommandRunner;
	}

	/**
	 * Отображает захваченную PHP-ошибку.
	 * Метод отображает ошибку в консольном режиме в отсутствие активного
	 * обработчика ошибок.
	 * @param integer $code код ошибки
	 * @param string $message сообщение ошибки
	 * @param string $file файл, в котором произошла ошибка
	 * @param string $line строка, в которой произошла ошибка
	 */
	public function displayError($code,$message,$file,$line)
	{
		echo "PHP Error[$code]: $message\n";
		echo "    in file $file at line $line\n";
		$trace=debug_backtrace();
		// skip the first 4 stacks as they do not tell the error position
		if(count($trace)>4)
			$trace=array_slice($trace,4);
		foreach($trace as $i=>$t)
		{
			if(!isset($t['file']))
				$t['file']='unknown';
			if(!isset($t['line']))
				$t['line']=0;
			if(!isset($t['function']))
				$t['function']='unknown';
			echo "#$i {$t['file']}({$t['line']}): ";
			if(isset($t['object']) && is_object($t['object']))
				echo get_class($t['object']).'->';
			echo "{$t['function']}()\n";
		}
	}

	/**
	 * Отображает неперехваченное PHP-исключение.
	 * Метод отображает исключение в консольном режиме в отсутствие
	 * активного обработчика ошибок.
	 * @param Exception $exception неперехваченное исключение
	 */
	public function displayException($exception)
	{
		echo $exception;
	}

	/**
	 * @return string директория, содержащая классы команды. По умолчанию 'protected/commands'
	 */
	public function getCommandPath()
	{
		$applicationCommandPath = $this->getBasePath().DIRECTORY_SEPARATOR.'commands';
		if($this->_commandPath===null && file_exists($applicationCommandPath))
			$this->setCommandPath($applicationCommandPath);
		return $this->_commandPath;
	}

	/**
	 * @param string $value директория, содержащая классы команды
	 * @throws CException вызывается, если директория неверна
	 */
	public function setCommandPath($value)
	{
		if(($this->_commandPath=realpath($value))===false || !is_dir($this->_commandPath))
			throw new CException(Yii::t('yii','The command path "{path}" is not a valid directory.',
				array('{path}'=>$value)));
	}

	/**
	 * Возвращает экземпляр исполнителя (runner) команд.
	 * @return CConsoleCommandRunner экземпляр исполнителя (runner) команд
	 */
	public function getCommandRunner()
	{
		return $this->_runner;
	}
}
