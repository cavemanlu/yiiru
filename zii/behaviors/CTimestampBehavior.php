<?php
/**
 * Файл класса CTimestampBehavior.
 *
 * @author Jonah Turnquist <poppitypop@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */
 
 /**
 * Поведение CTimestampBehavior автоматически заполняет дату и время связанных
 * атрибутов при создании и/или обновлении active record-объекта.
 * 
 * Вы можете настроить модель active record для использования данного поведения следующим образом:
 * <pre>
 * public function behaviors(){
 * 	return array(
 * 		'CTimestampBehavior' => array(
 * 			'class' => 'zii.behaviors.CTimestampBehavior',
 * 			'createAttribute' => 'create_time_attribute',
 * 			'updateAttribute' => 'update_time_attribute',
 * 		)
 * 	);
 * }
 * </pre>
 * Свойства {@link createAttribute} {@link updateAttribute} по умолчанию имеют значения 'create_time' 'update_time',
 * поэтому не обязательно их настраивать. Если вы не хотите, чтобы поведение CTimestampBehavior
 * устанавливало временную отметку при обновлении или создании записи, установите соответствующий атрибут в null.
 *
 * По умолчанию атрибут обновления устанавливается только при обновлении записи. Если вы хотите, чтобы он устанавилвался и при
 * создании записи, установите свойство {@link setUpdateOnCreate} в значение true.
 *
 * Хотя CTimestampBehavior пытается выяснить сам, какое значение присваивать атрибуту временной метки,
 * вы можете настроить это значение свойством {@link timestampExpression}
 *
 * @author Jonah Turnquist <poppitypop@gmail.com>
 * @version $Id: CTimestampBehavior.php 3229 2011-05-21 00:20:29Z alexander.makarow $
 * @package zii.behaviors
 * @since 1.1
 */

class CTimestampBehavior extends CActiveRecordBehavior {
	/**
	* @var mixed имя атрибута, хранящего время создания. Значение null означает,
	* что для атрибута создания не используется временная метка. По умолчанию - 'create_time'
	*/
	public $createAttribute = 'create_time';
	/**
	* @var mixed имя атрибута, хранящего время модификации. Значение null означает,
	* что для атрибута обновления не используется временная метка. По умолчанию - 'update_time'
	*/
	public $updateAttribute = 'update_time';
	
	/**
	* @var bool устанавливать ли атрибут обновления в значение времени создания при создании записи.
	* По умолчанию - false
	*/
	public $setUpdateOnCreate = false;

	/**
	* @var mixed выражение, используемое для генерации временной метки.
	* Может быть либо строкой, представляющей PHP-выражение (например, 'time()'), либо
	* обектом класса {@link CDbExpression}, представляющим выражение базы данных (например, new CDbExpression('NOW()')).
	* По умолчанию - null, т.е., мы пытаемся выяснить подходящую временную метку автоматически.
	* Если подходящая временная метка не определена, то будет использовано текущее UNIX-время
	*/
	public $timestampExpression;

	/**
	* @var array карта соответствий типов столбцов таблицы методам базы данных
	*/
	protected static $map = array(
			'datetime'=>'NOW()',
			'timestamp'=>'NOW()',
			'date'=>'NOW()',
	);

	/**
	* Реагирует на событие {@link CModel::onBeforeSave}.
	* Устанавливает значения отрибутов создания или модификации согласно настройкам
	*
	* @param CModelEvent $event параметр события
	*/
	public function beforeSave($event) {
		if ($this->getOwner()->getIsNewRecord() && ($this->createAttribute !== null)) {
			$this->getOwner()->{$this->createAttribute} = $this->getTimestampByAttribute($this->createAttribute);
		}
		if ((!$this->getOwner()->getIsNewRecord() || $this->setUpdateOnCreate) && ($this->updateAttribute !== null)) {
			$this->getOwner()->{$this->updateAttribute} = $this->getTimestampByAttribute($this->updateAttribute);
		}
	}

	/**
	* Получает подходящую временную отметку по переданному атрибуту
	* 
	* @param string $attribute атрибут
	* @return mixed временная отметка (unix-время или функция mysql)
	*/
	protected function getTimestampByAttribute($attribute) {
		if ($this->timestampExpression instanceof CDbExpression)
			return $this->timestampExpression;
		else if ($this->timestampExpression !== null)
			return @eval('return '.$this->timestampExpression.';');

		$columnType = $this->getOwner()->getTableSchema()->getColumn($attribute)->dbType;
		

	/**
	* Возвращает подходящую временную отметку по переданному типу столбца атрибута
	* 
	* @param string $columnType тип столбца
	* @return mixed временная отметка (unix-время или функция mysql)
	*/
	protected function getTimestampByColumnType($columnType) {
		return isset(self::$map[$columnType]) ? new CDbExpression(self::$map[$columnType]) : time();
	}
}