<?php
/**
 * Файл содержит базовый класс для классов компонентов приложения.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Класс CApplicationComponent - это базовый класс для классов компонентов приложения.
 *
 * CApplicationComponent реализует методы, объявленные в интерфейсе {@link IApplicationComponent}.
 *
 * При разработке компонента приложения старайтесь помещать код инициализации компонента приложения в
 * метод {@link init()}, а не в конструктор. Преимущество такого подхода в том, что
 * компонент приложения может быть настроен через конфигурацию приложения.
 *
 * @property boolean $isInitialized был ли компонент приложения инициализирован
 * (т.е., вызывался ли метод {@link init()})
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CApplicationComponent.php 3515 2011-12-28 12:29:24Z mdomba $
 * @package system.base
 * @since 1.0
 */
abstract class CApplicationComponent extends CComponent implements IApplicationComponent
{
	/**
	 * @var array присоединяемые к компоненту поведения.
	 * Поведения присоединяются к компоненту при вызове метода {@link init}.
	 * Обратитесь к {@link CModel::behaviors}, чтобы узнать, как определить
	 * значение данного свойства
	 */
	public $behaviors=array();

	private $_initialized=false;

	/**
	 * Инициализирует компонент приложения.
	 * Метод требуется интерфейсом {@link IApplicationComponent} и вызывается
	 * приложением. Если вы переопределите данный метод, убедитесь, что
	 * реализация метода классом-родителем вызывается так, чтобы компонент
	 * приложения был помечен как инициализированный
	 */
	public function init()
	{
		$this->attachBehaviors($this->behaviors);
		$this->_initialized=true;
	}

	/**
	 * Проверяет, был ли компонент приложения инициализирован
	 * @return boolean был ли компонент приложения инициализирован (т.е., вызывался ли метод {@link init()})
	 */
	public function getIsInitialized()
	{
		return $this->_initialized;
	}
}
