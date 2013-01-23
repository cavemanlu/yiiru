<?php
/**
 * Файл класса CBehavior.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright 2008-2013 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * CBehavior - это базовый класс для классов поведений.
 *
 * @property CComponent $owner компонент, к которому присоединено поведение
 * @property boolean $enabled активно ли поведение
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package system.base
 */
class CBehavior extends CComponent implements IBehavior
{
	private $_enabled=false;
	private $_owner;

	/**
	 * Объявляет события и их обработчики.
	 * События определяются {@link owner компонентом-собственником}, в то время как обработчики -
	 * классом поведения. Обработчики будут присоединены к соответствующим событиям, когда
	 * поведение присоединено к {@link owner компоненту-собственнику}, и будут отсоединены от событий,
	 * когда поведение отсоединено от компонента. Убедитесь, что обработчик определен как public
	 * @return array события (ключи массива) и их имена соответствующих методов-обработчиков (значения массива).
	 */
	public function events()
	{
		return array();
	}

	/**
	 * Присоединяет объект поведения к компоненту.
	 * Реализация по умолчанию устанавливает свойство {@link owner} и
	 * присоединяет обработчик события как это объявлено в методе {@link events}.
	 * This method will also set {@link enabled} to true.
	 * Убедитесь, что обработчик определен как public и что вызывается реализация метода родителя, если переопределен данный метод
	 * @param CComponent $owner компонент, к которому присоединяется поведение
	 */
	public function attach($owner)
	{
		$this->_enabled=true;
		$this->_owner=$owner;
		$this->_attachEventHandlers();
	}

	/**
	 * Отсоединяет поведение от компонента.
	 * Реализация по умолчанию очищает свойство {@link owner} и
	 * отсоединяет обработчик события, объявленный в методе {@link events}.
	 * This method will also set {@link enabled} to false.
	 * Убедитесь, что вызываете реализацию метода родителя, если переопределяете данный метод.
	 * @param CComponent $owner компонент, от которого отсоединяется поведение.
	 */
	public function detach($owner)
	{
		foreach($this->events() as $event=>$handler)
			$owner->detachEventHandler($event,array($this,$handler));
		$this->_owner=null;
		$this->_enabled=false;
	}

	/**
	 * @return CComponent компонент, к которому присоединено поведение.
	 */
	public function getOwner()
	{
		return $this->_owner;
	}

	/**
	 * @return boolean активно ли поведение
	 */
	public function getEnabled()
	{
		return $this->_enabled;
	}

	/**
	 * @param boolean $value активно ли поведение
	 */
	public function setEnabled($value)
	{
		$value=(bool)$value;
		if($this->_enabled!=$value && $this->_owner)
		{
			if($value)
				$this->_attachEventHandlers();
			else
			{
				foreach($this->events() as $event=>$handler)
					$this->_owner->detachEventHandler($event,array($this,$handler));
			}
		}
		$this->_enabled=$value;
	}

	private function _attachEventHandlers()
	{
		$class=new ReflectionClass($this);
		foreach($this->events() as $event=>$handler)
		{
			if($class->getMethod($handler)->isPublic())
				$this->_owner->attachEventHandler($event,array($this,$handler));
		}
	}
}
