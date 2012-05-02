<?php
/**
 * Файл класса CConsoleCommandBehavior.
 *
 * @author Evgeny Blinov <e.a.blinov@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Класс CConsoleCommandBehavior - это базовый класс поведений, присоединяемых
 * к компоненту консольной команды
 *
 * @property CConsoleCommand $owner консольная команда, к которой присоединено
 * данное поведение
 *
 * @author Evgeny Blinov <e.a.blinov@gmail.com>
 * @package system.console
 * @since 1.1.11
 */
class CConsoleCommandBehavior extends CBehavior
{
	/**
	 * Определяет события и их соответствующие методы-обработчики. По умолчанию
	 * возвращаются события и их методы - 'onBeforeAction' и 'onAfterAction'.
	 * При переопределении данного метода, необходимо убедиться, что результат
	 * родительской реализации метода также возвращается
	 * @return array события (ключи массива) и соответствующие им
	 * методы-обработчики (значения массива)
	 * @see CBehavior::events
	 */
	public function events()
	{
		return array(
		    'onBeforeAction' => 'beforeAction',
		    'onAfterAction' => 'afterAction'
		);
	}
	/**
	 * Выполняется по событию {@link CConsoleCommand::onBeforeAction}.
	 * Для обработки соответствующего события {@link CBehavior::owner консольной команды},
	 * необходимо переопределить данный метод
	 * @param CConsoleCommandEvent $event параметр события
	 */
	protected function beforeAction($event)
	{
	}

	/**
	 * Выполняется по событию {@link CConsoleCommand::onAfterAction}.
	 * Для обработки соответствующего события {@link CBehavior::owner консольной команды},
	 * необходимо переопределить данный метод
	 * @param CConsoleCommandEvent $event параметр события
	 */
	protected function afterAction($event)
	{
	}
}