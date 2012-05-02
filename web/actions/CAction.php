<?php
/**
 * Файл класса CAction.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * CAction - это базовый класс для всех классов действий контроллера.
 *
 * CAction предоставляет способ разделения сложных котроллеров в меньшие
 * действия в разных файлах классов.
 *
 * Наследуемые классы должны реализовывать метод {@link run()}, вызываемый
 * контроллером при запросе действия.
 *
 * Экземпляр действия может получить доступ к своему контроллеру обращением к
 * свойству {@link getController controller}.
 *
 * @property CController $controller контроллер-владелец данного действия
 * @property string $id идентификатор действия
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CAction.php 3426 2011-10-25 00:01:09Z alexander.makarow $
 * @package system.web.actions
 * @since 1.0
 */
abstract class CAction extends CComponent implements IAction
{
	private $_id;
	private $_controller;

	/**
	 * Конструктор
	 * @param CController $controller контроллер-владелец данного действия
	 * @param string $id идентификатор действия
	 */
	public function __construct($controller,$id)
	{
		$this->_controller=$controller;
		$this->_id=$id;
	}

	/**
	 * @return CController контроллер-владелец данного действия
	 */
	public function getController()
	{
		return $this->_controller;
	}

	/**
	 * @return string идентификатор действия
	 */
	public function getId()
	{
		return $this->_id;
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
		$method=new ReflectionMethod($this, 'run');
		if($method->getNumberOfParameters()>0)
			return $this->runWithParamsInternal($this, $method, $params);
		else
			return $this->run();
	}

	/**
	 * Выполняет метод объекта с переданными именованными параметрами. Данный
	 * метод предназначен для внутреннего использования
	 * @param mixed $object объект, метод которого выполняется
	 * @param ReflectionMethod $method reflection-метод
	 * @param array $params именованные параметры
	 * @return boolean верны ли именованные параметры
	 * @since 1.1.7
	 */
	protected function runWithParamsInternal($object, $method, $params)
	{
		$ps=array();
		foreach($method->getParameters() as $i=>$param)
		{
			$name=$param->getName();
			if(isset($params[$name]))
			{
				if($param->isArray())
					$ps[]=is_array($params[$name]) ? $params[$name] : array($params[$name]);
				else if(!is_array($params[$name]))
					$ps[]=$params[$name];
				else
					return false;
			}
			else if($param->isDefaultValueAvailable())
				$ps[]=$param->getDefaultValue();
			else
				return false;
		}
		$method->invokeArgs($object,$ps);
		return true;
	}
}
