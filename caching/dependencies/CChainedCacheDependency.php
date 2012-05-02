<?php
/**
 * Файл класса CChainedCacheDependency.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Компонент CChainedCacheDependency представляет список зависимостей кэша.
 *
 * Если любая из зависимостей сообщает об изменении, CChainedCacheDependency
 * возвратит значение true при проверке.
 *
 * Для добавления зависимостей в цепочку CChainedCacheDependency используйте
 * свойство {@link getDependencies Dependencies}, возвращающее экземпляр класса
 * {@link CTypedList} и может быть использовано как массив
 * (за подробностями обратитесь к классу {@link CList}).
 *
 * @property CTypedList $dependencies список объектов зависимости
 * @property boolean $hasChanged изменилась ли зависимость
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CChainedCacheDependency.php 3515 2011-12-28 12:29:24Z mdomba $
 * @package system.caching.dependencies
 * @since 1.0
 */
class CChainedCacheDependency extends CComponent implements ICacheDependency
{
	private $_dependencies=null;

	/**
	 * Конструктор.
	 * @param array $dependencies зависимости, добавляемые в данную цепочку
	 * @since 1.1.4
	 */
	public function __construct($dependencies=array())
	{
		if(!empty($dependencies))
			$this->setDependencies($dependencies);
	}

	/**
	 * @return CTypedList список объектов зависимости
	 */
	public function getDependencies()
	{
		if($this->_dependencies===null)
			$this->_dependencies=new CTypedList('ICacheDependency');
		return $this->_dependencies;
	}

	/**
	 * @param array $values список объектов зависимости или конфигураций, добавляемых к данной цепочке.
	 * Если зависимость определена конфигурацией, то конфигурация должна быть массивом, который
	 * может быть распознан методом {@link YiiBase::createComponent}
	 */
	public function setDependencies($values)
	{
		$dependencies=$this->getDependencies();
		foreach($values as $value)
		{
			if(is_array($value))
				$value=Yii::createComponent($value);
			$dependencies->add($value);
		}
	}

	/**
	 * Выполняет зависимость, генерируя и сохраняя данные, связанные с зависимостью.
	 */
	public function evaluateDependency()
	{
		if($this->_dependencies!==null)
		{
			foreach($this->_dependencies as $dependency)
				$dependency->evaluateDependency();
		}
	}

	/**
	 * Выполняет фактическую проверку зависимости.
	 * Метод возвращает значение true, если любой из объектов зависимости сообщил об изменении зависимости.
	 * @return boolean изменилась ли зависимость
	 */
	public function getHasChanged()
	{
		if($this->_dependencies!==null)
		{
			foreach($this->_dependencies as $dependency)
				if($dependency->getHasChanged())
					return true;
		}
		return false;
	}
}
