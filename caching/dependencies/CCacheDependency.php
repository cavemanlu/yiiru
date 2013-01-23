<?php
/**
 * Файл класса CCacheDependency.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright 2008-2013 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Класс CCacheDependency - это базовый класс для классов зависимостей кэша.
 *
 * Класс CCacheDependency реализует интерфейс {@link ICacheDependency}.
 * Класс-потомки должны переопределять метод {@link generateDependentData} для
 * фактической проверки зависимости.
 *
 * @property boolean $hasChanged изменилась ли зависимость
 * @property mixed $dependentData данные, используемые для определения
 * изменения зависимости. Данные доступны после вызова метода
 * {@link evaluateDependency}
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package system.caching.dependencies
 * @since 1.0
 */
class CCacheDependency extends CComponent implements ICacheDependency
{
	/**
	 * @var boolean используется ли данная зависимость повторно. Если
	 * установлено в значение true, то зависимые данные для данной зависимости
	 * будут сгенерированы только один раз на запрос. В дальнейшем можно
	 * использовать ту же зависимость для нескольких различных вызовов кеша на
	 * одной странице без перепроверки зависимости. По умолчнию - false
	 * @since 1.1.11
	 */
	public $reuseDependentData=false;

	/**
	 * @var array кешированные данные для повторно используемых зависимостей
	 * @since 1.1.11
	 */
	private static $_reusableData=array();

	private $_hash;
	private $_data;

	/**
	 * Выполняет зависимость, генерируя и сохраняя данные, связанные с зависимостью.
	 * Метод вызывается кэшем перед записью в него данных.
	 */
	public function evaluateDependency()
	{
		if ($this->reuseDependentData)
		{
			$hash=$this->getHash();
			if (!isset(self::$_reusableData[$hash]['dependentData']))
				self::$_reusableData[$hash]['dependentData']=$this->generateDependentData();
			$this->_data=self::$_reusableData[$hash]['dependentData'];
		}
		else
			$this->_data=$this->generateDependentData();
	}

	/**
	 * @return boolean изменилась ли зависимость
	 */
	public function getHasChanged()
	{
		if ($this->reuseDependentData)
		{
			$hash=$this->getHash();
			if (!isset(self::$_reusableData[$hash]['hasChanged']))
			{
				if (!isset(self::$_reusableData[$hash]['dependentData']))
					self::$_reusableData[$hash]['dependentData']=$this->generateDependentData();
				self::$_reusableData[$hash]['hasChanged']=self::$_reusableData[$hash]['dependentData']!=$this->_data;
			}
			return self::$_reusableData[$hash]['hasChanged'];
		}
		else
			return $this->generateDependentData()!=$this->_data;
	}

	/**
	 * @return mixed данные, используемые для определения изменения
	 * зависимости. Данные доступны после вызова метода
	 * {@link evaluateDependency}
	 */
	public function getDependentData()
	{
		return $this->_data;
	}

	/**
	 * Генерирует данные, необходимые для определения изменения зависимости.
	 * Класс-потомки должны переопределять данный метод для генерации
	 * фактических данных зависимости
	 * @return mixed данные, необходимые для определения изменения зависимости
	 */
	protected function generateDependentData()
	{
		return null;
	}
	/**
	 * Генерирует уникальный хеш, идентифицирующий данную зависимость
	 * @return string уникальный хеш, идентифицирующий данную зависимость
	 */
	private function getHash()
	{
		if($this->_hash===null)
			$this->_hash=sha1(serialize($this));
		return $this->_hash;
	}
}