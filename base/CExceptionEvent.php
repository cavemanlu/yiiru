<?php
/**
 * Файл класса CExceptionEvent.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Класс CExceptionEvent представляет параметр для события {@link CApplication::onException onException}.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CExceptionEvent.php 2799 2011-01-01 19:31:13Z qiang.xue $
 * @package system.base
 * @since 1.0
 */
class CExceptionEvent extends CEvent
{
	/**
	 * @var CException исключение для данного события.
	 */
	public $exception;

	/**
	 * Конструктор.
	 * @param mixed $sender отправитель события
	 * @param CException $exception исключение
	 */
	public function __construct($sender,$exception)
	{
		$this->exception=$exception;
		parent::__construct($sender);
	}
}