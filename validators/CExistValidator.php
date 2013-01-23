<?php
/**
 * Файл класса CExistValidator.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright 2008-2013 Yii Software LLC
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
 * @package system.validators
 */
class CExistValidator extends CValidator
{
	/**
	 * @var boolean whether the comparison is case sensitive. Defaults to true.
	 * Note, by setting it to false, you are assuming the attribute type is string.
	 */
	public $caseSensitive=true;
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
	 * @var mixed additional query criteria. Either an array or CDbCriteria.
	 * This will be combined with the condition that checks if the attribute
	 * value exists in the corresponding table column.
	 * This array will be used to instantiate a {@link CDbCriteria} object.
	 
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

		$columnName=$column->rawName;
		$criteria=new CDbCriteria();
		if($this->criteria!==array())
			$criteria->mergeWith($this->criteria);
		$tableAlias = empty($criteria->alias) ? $finder->getTableAlias(true) : $criteria->alias;
		$valueParamName = CDbCriteria::PARAM_PREFIX.CDbCriteria::$paramCount++;
		$criteria->addCondition($this->caseSensitive ? "{$tableAlias}.{$columnName}={$valueParamName}" : "LOWER({$tableAlias}.{$columnName})=LOWER({$valueParamName})");
		$criteria->params[$valueParamName] = $value;

		if(!$finder->exists($criteria))
		{
			$message=$this->message!==null?$this->message:Yii::t('yii','{attribute} "{value}" is invalid.');
			$this->addError($object,$attribute,$message,array('{value}'=>CHtml::encode($value)));
		}
	}
}

