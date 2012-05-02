<?php
/**
 * Файл класса CJuiInputWidget.
 *
 * @author Sebastian Thierer <sebathi@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

Yii::import('zii.widgets.jui.CJuiWidget');

/**
 * Класс CJuiInputWidget - это базовый класс для виджетов Juery UI, которые могут собирать введенные пользователем данные.
 *
 * @author Sebastian Thierer <sebathi@gmail.com>
 * @version $Id: CJuiInputWidget.php 2799 2011-01-01 19:31:13Z qiang.xue $
 * @package zii.widgets.jui
 * @since 1.1
 */
abstract class CJuiInputWidget extends CJuiWidget
{
	/**
	 * @var CModel модель данных, ассоциированная с виджетом
	 */
	public $model;
	/**
	 * @var string атрибут модели, ассоциированный с виджетом. Имя может содержать квадратные
	 * скобки (например, 'name[1]') для использования массового сбора данных
	 */
	public $attribute;
	/**
	 * @var string имя тега вводимых данных. Свойство должно быть установлено, если не установлено свойство {@link model}
	 */
	public $name;
	/**
	 * @var string значение вводимых данных
	 */
	public $value;


	/**
	 * @return array имя и идентификатор тега вводимых данных
	 */
	protected function resolveNameID()
	{
		if($this->name!==null)
			$name=$this->name;
		else if(isset($this->htmlOptions['name']))
			$name=$this->htmlOptions['name'];
		else if($this->hasModel())
			$name=CHtml::activeName($this->model,$this->attribute);
		else
			throw new CException(Yii::t('zii','{class} must specify "model" and "attribute" or "name" property values.',array('{class}'=>get_class($this))));

		if(($id=$this->getId(false))===null)
		{
			if(isset($this->htmlOptions['id']))
				$id=$this->htmlOptions['id'];
			else
				$id=CHtml::getIdByName($name);
		}

		return array($name,$id);
	}

	/**
	 * @return boolean ассоциирован ли виджет с моделью данных
	 */
	protected function hasModel()
	{
		return $this->model instanceof CModel && $this->attribute!==null;
	}
}
