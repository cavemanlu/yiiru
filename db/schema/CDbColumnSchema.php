<?php
/**
 * Файл класса CDbColumnSchema.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Класс CDbColumnSchema описывает метаданные столбца таблицы базы данных.
 *
 * Может использоваться в AR-объектах в методах запросов таких, как
 * CActiveRecord::find and CActiveRecord::findAll.
 *
 * $criteria=new CDbCriteria();
 * $criteria->compare('status',Post::STATUS_ACTIVE);
 * $criteria->addInCondition('id',array(1,2,3,4,5,6));
 *
 * $posts = Post::model()->findAll($criteria);
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package system.db.schema
 * @since 1.0
 */
class CDbColumnSchema extends CComponent
{
	/**
	 * @var string имя столбца (без кавычек)
	 */
	public $name;
	/**
	 * @var string исходное имя столбца. Экранированное имя столбца, пригодное для использования в SQL-запросах
	 */
	public $rawName;
	/**
	 * @var boolean может ли данный столбец иметь значение null
	 */
	public $allowNull;
	/**
	 * @var string тип столбца в базе данных
	 */
	public $dbType;
	/**
	 * @var string тип столбца в PHP-скриптах
	 */
	public $type;
	/**
	 * @var mixed значение столбца по умолчанию
	 */
	public $defaultValue;
	/**
	 * @var integer размер столбца
	 */
	public $size;
	/**
	 * @var integer точность данных столбца, если данные числовые
	 */
	public $precision;
	/**
	 * @var integer масштаб данных столбца, если данные числовые
	 */
	public $scale;
	/**
	 * @var boolean является ли данный столбец первичным ключом
	 */
	public $isPrimaryKey;
	/**
	 * @var boolean является ли данный столбец внешним ключом
	 */
	public $isForeignKey;
	/**
	 * @var boolean является ли столбец автоинкрементным
	 * @since 1.1.7
	 */
	public $autoIncrement=false;
	/**
	 * @var string comment of this column. Default value is empty string which means that no comment
	 * has been set for the column. Null value means that RDBMS does not support column comments
	 * at all (SQLite) or comment retrieval for the active RDBMS is not yet supported by the framework.
	 * @since 1.1.13
	 */
	public $comment='';


	/**
	 * Инициализирует столбец типом в БД и значением по умолчанию.
	 * Устанавливает тип столбца в PHP-скриптах, размер, точность, масштаб
	 * соответствующими значению по умолчанию
	 * @param string $dbType тип столбца в БД
	 * @param mixed $defaultValue значение по умолчанию
	 */
	public function init($dbType, $defaultValue)
	{
		$this->dbType=$dbType;
		$this->extractType($dbType);
		$this->extractLimit($dbType);
		if($defaultValue!==null)
			$this->extractDefault($defaultValue);
	}

	/**
	 * Устанавливает тип столбца в PHP-скриптах по типу в БД
	 * @param string $dbType тип столбца в БД
	 */
	protected function extractType($dbType)
	{
		if(stripos($dbType,'int')!==false && stripos($dbType,'unsigned int')===false)
			$this->type='integer';
		elseif(stripos($dbType,'bool')!==false)
			$this->type='boolean';
		elseif(preg_match('/(real|floa|doub)/i',$dbType))
			$this->type='double';
		else
			$this->type='string';
	}

	/**
	 * Устанавливает размер, точность и масштаб по типу в БД
	 * @param string $dbType тип столбца в БД
	 */
	protected function extractLimit($dbType)
	{
		if(strpos($dbType,'(') && preg_match('/\((.*)\)/',$dbType,$matches))
		{
			$values=explode(',',$matches[1]);
			$this->size=$this->precision=(int)$values[0];
			if(isset($values[1]))
				$this->scale=(int)$values[1];
		}
	}

	/**
	 * Устанавливает значение столбца по умолчанию.
	 * Проходит преобразование типа для правильности типа в PHP-скриптах
	 * @param mixed $defaultValue значение столбца по умолчанию, полученное из метаданных
	 */
	protected function extractDefault($defaultValue)
	{
		$this->defaultValue=$this->typecast($defaultValue);
	}

	/**
	 * Преобразует входное значение в тип, соответствующий типу столбца
	 * @param mixed $value входное значение
	 * @return mixed сконвертированное значение
	 */
	public function typecast($value)
	{
		if(gettype($value)===$this->type || $value===null || $value instanceof CDbExpression)
			return $value;
		if($value==='' && $this->allowNull)
			return $this->type==='string' ? '' : null;
		switch($this->type)
		{
			case 'string': return (string)$value;
			case 'integer': return (integer)$value;
			case 'boolean': return (boolean)$value;
			case 'double':
			default: return $value;
		}
	}
}
