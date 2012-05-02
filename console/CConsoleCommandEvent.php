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
	 * установлено в значение false обработчиком события, то действие
	 * консольной команды прекратится после обработки данного события. Если
	 * своуство установленов значение true, то продолжится обычный цикл
	 * выполнения, включая выполнение действия и вызова метода
	 * {@link CConsoleCommand::afterAction}
	 */
	public $stopCommand=false;

	/**
	 * Конструктор
	 * @param mixed $sender объект, вызвавший событие
	 * @param string $params параметры, передаваемые в метод действия
	 * @param string $action имя действия
	 */
	public function __construct($sender=null,$params=null,$action=null){
		parent::__construct($sender,$params);
		$this->action=$action;
	}
}