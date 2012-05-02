<?php
/**
 * Файл класса CDbException.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Класс CDbException представляет исключения, вызываемые некоторыми операциями, связанными с базой данных.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CDbException.php 2799 2011-01-01 19:31:13Z qiang.xue $
 * @package system.db
 * @since 1.0
 */
class CDbException extends CException
{
	/**
	 * @var mixed информация об ошибке, предоставляемая исключением PDO. То же самое, что возвращается методом
	 * {@link http://www.php.net/manual/en/pdo.errorinfo.php PDO::errorInfo}
	 * @since 1.1.4
	 */
	public $errorInfo;

	/**
	 * Конструктор
	 * @param string $message сообщение об ошибке PDO
	 * @param integer $code код ошибки PDO
	 * @param mixed $errorInfo PDO информация об ошибке
	 */
	public function __construct($message,$code=0,$errorInfo=null)
	{
		$this->errorInfo=$errorInfo;
		parent::__construct($message,$code);
	}
}