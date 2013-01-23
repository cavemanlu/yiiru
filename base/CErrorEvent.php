<?php
/**
 * Файл класса CErrorEvent.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright 2008-2013 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Класс CErrorEvent представляет параметр для события {@link CApplication::onError onError}.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package system.base
 * @since 1.0
 */
class CErrorEvent extends CEvent
{
	/**
	 * @var string код ошибки
	 */
	public $code;
	/**
	 * @var string сообщение об ошибке
	 */
	public $message;
	/**
	 * @var string файл, в котором произошла ошибка
	 */
	public $file;
	/**
	 * @var string строка, в которой произошла ошибка
	 */
	public $line;

	/**
	 * Конструктор.
	 * @param mixed $sender отправитель события
	 * @param string $code код ошибки
	 * @param string $message сообщение об ошибке
	 * @param string $file файл, в котором произошла ошибка
	 * @param integer $line строка, в которой произошла ошибка
	 */
	public function __construct($sender,$code,$message,$file,$line)
	{
		$this->code=$code;
		$this->message=$message;
		$this->file=$file;
		$this->line=$line;
		parent::__construct($sender);
	}
}
