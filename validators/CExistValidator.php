<?php
/**
 * Файл класса CExistValidator.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Валидатор CExistValidator проверяет наличие значения атрибута в таблице.
 *
 * Данный валидатор часто используется для проверки того, что внешний ключ содержит значение,
 * которое может быть найдено во внешней таблице.
 *
 * При использовании свойства {@link message} для определения сообщения об
 * ошибке сообщение может содержать дополнительные метки, которые будут
 * заменены реальным содержимым. В дополнение к метке "{attribute}",
 * распознаваемой всеми валидаторами (см. {@link CValidator}),
 * CExistValidator позволяет определять следующую метку:
 * <ul>
 * <li>{value}: заменяется значением атрибута.</li>
 * </ul>
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CExistValidator.php 3549 2012-01-27 15:36:43Z qiang.xue $
 * @package system.validators
 */
class CExistValidator extends CValidator
{
	/**
	 * @var string имя ActiveRecord-класса, используемое для поиска валидируемого атрибута.
	 * По умолчанию - null, т.е. использование ActiveRecord-класса валидируемого атрибута.
	 * Вы можете использовать здесь псевдонимы (и пути) для ссылки на имя класса.
	 * @see attributeName
	 */
	public $className;
	/**
	 * @var string имя атрибута ActiveRecord-класса, используемое для поиска значения валидируемого атрибута.
	 * По умолчанию - null, т.е. использование имени валидируемого атрибута.
	 * @see className
	 */
	public $attributeName;
	/**
	 * @var array дополнительный критерий запроса. Будет объединен с условием,
	 * проверяющим существование значения атрибута в соответствующем столбце таблицы.
	 * Данный массив будет использован для создания экземпляра {@link CDbCriteria}
	 */
	public $criteria=array();
	/**
	 * @var boolean может ли быть значение атрибута пустым или равным null. По умолчанию - true,
	 * т.е. пустой атрибут считается валидным
	 */
	public $allowEmpty=true;

	/**
	 * Валидирует отдельный атрибут.
	 * При возникновении ошибки к объекту добавляется сообщение об ошибке.
	 * @param CModel $object валидируемый объект данных
	 * @param string $attribute имя валидируемого атрибута
	 */
	protected function validateAttribute($object,$attribute)
	{
		$value=$object->$attribute;
		if($this->allowEmpty && $this->isEmpty($value))
			return;

		$className=$this->className===null?get_class($object):Yii::import($this->className);
		$attributeName=$this->attributeName===null?$attribute:$this->attributeName;
		$finder=CActiveRecord::model($className);
		$table=$finder->getTableSchema();
		if(($column=$table->getColumn($attributeName))===null)
			throw new CException(Yii::t('yii','Table "{table}" does not have a column named "{column}".',
				array('{column}'=>$attributeName,'{table}'=>$table->name)));

		$criteria=array('condition'=>$column->rawName.'=:vp','params'=>array(':vp'=>$value));
		if($this->criteria!==array())
		{
			$criteria=new CDbCriteria($criteria);
			$criteria->mergeWith($this->criteria);
		}

		if(!$finder->exists($criteria))
		{
			$message=$this->message!==null?$this->message:Yii::t('yii','{attribute} "{value}" is invalid.');
			$this->addError($object,$attribute,$message,array('{value}'=>CHtml::encode($value)));
		}
	}
}

