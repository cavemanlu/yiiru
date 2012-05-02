<?php
/**
 * Файл класса CFormStringElement.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Класс CFormStringElement представляет строку в форме.
 *
 * @property string $on имена сценариев, разделенные запятыми. По умолчанию - null
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CFormStringElement.php 3426 2011-10-25 00:01:09Z alexander.makarow $
 * @package system.web.form
 * @since 1.1
 */
class CFormStringElement extends CFormElement
{
	/**
	 * @var string содержимое строки
	 */
	public $content;

	private $_on;

	/**
	 * Возвращает значение, показывающее, в каких сценариях видима данная
	 * строка. Если значение пусто, то строка видима во всех сценариях. Иначе
	 * данная строка будет видима только в том случае, когда модель находится
	 * в сценарии, имя которого задано в данном свойстве. За подробной
	 * информацией о сценариях модели обратитесь к описанию свойства
	 * {@link CModel::scenario}
	 * @return string имена сценариев, разделенные запятыми. По умолчанию - null
	 */
	public function getOn()
	{
		return $this->_on;
	}

	/**
	 * @param string $value имена сценариев, разделенные запятыми
	 */
	public function setOn($value)
	{
		$this->_on=preg_split('/[\s,]+/',$value,-1,PREG_SPLIT_NO_EMPTY);
	}

	/**
	 * Генерирует данный элемент. Реализация по умолчанию просто возвращает
	 * сзначение свойства {@link content}
	 * @return string содержимое строки
	 */
	public function render()
	{
		return $this->content;
	}

	/**
	 * Определяет видимость данного элемента. Данный элемент проверяет, что
	 * свойство {@link on} содержит имя сценария, в котором находится модель
	 * @return boolean видим ли данный элемент
	 */
	protected function evaluateVisible()
	{
		return empty($this->_on) || in_array($this->getParent()->getModel()->getScenario(),$this->_on);
	}
}
