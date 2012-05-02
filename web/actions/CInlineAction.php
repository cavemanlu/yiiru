<?php
/**
 * Файл класса CInlineAction.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */


/**
 * Класс CInlineAction представляет действие, определенное как метод контроллера.
 *
 * Имя метода - вида 'actionXYZ', где 'XYZ' - имя действия.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CInlineAction.php 3137 2011-03-28 11:08:06Z mdomba $
 * @package system.web.actions
 * @since 1.0
 */
class CInlineAction extends CAction
{
	/**
	 * Выполняет действие. Вызывает метод действия, определенный в контроллере.
	 * Метод требуется классом {@link CAction}
	 */
	public function run()
	{
		$method='action'.$this->getId();
		$this->getController()->$method();
	}

	/**
	 * Выполняет действие с переданными параметрами запроса. Данный метод
	 * вызывается методом {@link CController::runAction()}
	 * @param array $params параметры запроса (имя => значение)
	 * @return boolean верны ли параметры запроса
	 * @since 1.1.7
	 */
	public function runWithParams($params)
	{
		$methodName='action'.$this->getId();
		$controller=$this->getController();
		$method=new ReflectionMethod($controller, $methodName);
		if($method->getNumberOfParameters()>0)
			return $this->runWithParamsInternal($controller, $method, $params);
		else
			return $controller->$methodName();
	}

}
