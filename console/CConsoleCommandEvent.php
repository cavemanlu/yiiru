<?php
/**
 * Файл класса CConsoleCommandEvent.
 *
 * @author Evgeny Blinov <e.a.blinov@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Класс CConsoleCommandEvent.
 *
 * CConsoleCommandEvent представляет параметры события, требуемые событиями,
 * вызываемыми консольной командой.
 *
 * @author Evgeny Blinov <e.a.blinov@gmail.com>
 * @package system.console
 * @since 1.1.11
 */
class CConsoleCommandEvent extends CEvent
{
	/**
	 * @var string имя действия
	 */
	public $action;
	/**
	 * @var boolean должно ли действие быть выполнено. Если данное свойство
	 * установлено в значение true обработчиком события, то действие
	 * консольной команды прекратится после обработки данного события. Если
	 * свойство установлено в значение false (по умолчанию), то продолжится
	 * обычный цикл выполнения, включая выполнение действия и вызова метода
	 * {@link CConsoleCommand::afterAction}
	 */
	public $stopCommand=false;
	/**
	 * @var integer exit code of application.
	 * This property is available in {@link CConsoleCommand::onAfterAction} event and will be set to the exit code
	 * returned by the console command action. You can set it to change application exit code.
	 */
	public $exitCode;

	/**
	 * Конструктор
	 * @param mixed $sender объект, вызвавший событие
	 * @param string $params параметры, передаваемые в метод действия
	 * @param string $action имя действия
	 * @param integer $exitCode the application exit code
	 */
	public function __construct($sender=null,$params=null,$action=null,$exitCode=0){
		parent::__construct($sender,$params);
		$this->action=$action;
		$this->exitCode=$exitCode;
	}
}