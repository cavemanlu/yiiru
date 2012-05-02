<?php
/**
 * Файл класса CHttpException.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Класс CHttpException представляет исключения, вызванные неправильными действиями конечного пользователя.
 *
 * Код ошибки HTTP может быть получен из свойства {@link statusCode}.
 * Обработчики ошибок могут использовать этот код для решения о том, как форматировать страницу ошибки.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CHttpException.php 2799 2011-01-01 19:31:13Z qiang.xue $
 * @package system.base
 * @since 1.0
 */
class CHttpException extends CException
{
	/**
	 * @var integer код ошибки HTTP, например, 403, 404, 500 и др.
	 */
	public $statusCode;

	/**
	 * Конструктор.
	 * @param integer $status код ошибки HTTP, например, 403, 404, 500 и др.
	 * @param string $message сообщение об ошибке
	 * @param integer $code код ошибки
	 */
	public function __construct($status,$message=null,$code=0)
	{
		$this->statusCode=$status;
		parent::__construct($message,$code);
	}
}
