<?php
/**
 * Файл класса CGlobalStateCacheDependency.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Компонент CGlobalStateCacheDependency представляет собой зависимость, основанную на значении глобального состояния.
 *
 * Компонент CGlobalStateCacheDependency проверяет, изменилось ли глобальное состояние.
 * Если изменилось, то зависимость помечается как измененная.
 * Для определения глобального состояния для проверки установите
 * в свойстве {@link stateName} имя глобального состояния.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CGlobalStateCacheDependency.php 2799 2011-01-01 19:31:13Z qiang.xue $
 * @package system.caching.dependencies
 * @since 1.0
 */
class CGlobalStateCacheDependency extends CCacheDependency
{
	/**
	 * @var string имя глобального состояния, значение которого используется для
	 * проверки изменения зависимости.
	 * @see CApplication::setGlobalState
	 */
	public $stateName;

	/**
	 * Конструктор.
	 * @param string $name имя глобального состояния
	 */
	public function __construct($name=null)
	{
		$this->stateName=$name;
	}

	/**
	 * Генерирует данные, необходимые для определения изменения зависимости.
	 * Метод возвращает значение глобального состояния.
	 * @return mixed данные, необходимые для определения изменения зависимости
	 */
	protected function generateDependentData()
	{
		if($this->stateName!==null)
			return Yii::app()->getGlobalState($this->stateName);
		else
			throw new CException(Yii::t('yii','CGlobalStateCacheDependency.stateName cannot be empty.'));
	}
}
