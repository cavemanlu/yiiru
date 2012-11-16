<?php
/**
 * Файл класса CHelpCommand.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Класс CHelpCommand представляет консольную команду помощи.
 *
 * CHelpCommand отображает список доступных команд или справочные инструкции об
 * определенной команде.
 *
 * Для использования, введите следующую строку в консоль:
 * <pre>
 * php path/to/entry_script.php help [имя команды]
 * </pre>
 * В коде выше, если имя команды не написано, на экран будут выведены все доступные команды.
 *
 * @property string $help описание команды
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package system.console
 * @since 1.0
 */
class CHelpCommand extends CConsoleCommand
{
	/**
	 * Выполняет действие.
	 * @param array $args параметры командной строки, специфичные для данной команды
	 * @return integer ненулевой код выхода из приложения после вывода справки
	 */
	public function run($args)
	{
		$runner=$this->getCommandRunner();
		$commands=$runner->commands;
		if(isset($args[0]))
			$name=strtolower($args[0]);
		if(!isset($args[0]) || !isset($commands[$name]))
		{
			if(!empty($commands))
			{
				echo "Yii command runner (based on Yii v".Yii::getVersion().")\n";
				echo "Usage: ".$runner->getScriptName()." <command-name> [parameters...]\n";
				echo "\nThe following commands are available:\n";
				$commandNames=array_keys($commands);
				sort($commandNames);
				echo ' - '.implode("\n - ",$commandNames);
				echo "\n\nTo see individual command help, use the following:\n";
				echo "   ".$runner->getScriptName()." help <command-name>\n";
			}
			else
			{
				echo "No available commands.\n";
				echo "Please define them under the following directory:\n";
				echo "\t".Yii::app()->getCommandPath()."\n";
			}
		}
		else
			echo $runner->createCommand($name)->getHelp();
		return 1;
	}

	/**
	 * Возвращает описание команды.
	 * @return string описание команды
	 */
	public function getHelp()
	{
		return parent::getHelp().' [command-name]';
	}
}