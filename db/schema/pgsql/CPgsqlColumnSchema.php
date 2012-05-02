<?php
/**
 * Файл класса CPgsqlColumnSchema.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Класс CPgsqlColumnSchema описывает метаданные столбца таблицы базы данных PostgreSQL.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CPgsqlColumnSchema.php 2799 2011-01-01 19:31:13Z qiang.xue $
 * @package system.db.schema.pgsql
 * @since 1.0
 */
class CPgsqlColumnSchema extends CDbColumnSchema
{
	/**
	 * Устанавливает тип столбца в PHP-скриптах по типу в БД
	 * @param string $dbType тип столбца в БД
	 */
	protected function extractType($dbType)
	{
		if(strpos($dbType,'[')!==false || strpos($dbType,'char')!==false || strpos($dbType,'text')!==false)
			$this->type='string';
		else if(strpos($dbType,'bool')!==false)
			$this->type='boolean';
		else if(preg_match('/(real|float|double)/',$dbType))
			$this->type='double';
		else if(preg_match('/(integer|oid|serial|smallint)/',$dbType))
			$this->type='integer';
		else
			$this->type='string';
	}

	/**
	 * Устанавливает значение столбца по умолчанию.
	 * Проходит преобразование типа для правильности типа в PHP-скриптах
	 * @param mixed $defaultValue значение столбца по умолчанию, полученное из метаданных
	 */
	protected function extractDefault($defaultValue)
	{
		if($defaultValue==='true')
			$this->defaultValue=true;
		else if($defaultValue==='false')
			$this->defaultValue=false;
		else if(strpos($defaultValue,'nextval')===0)
			$this->defaultValue=null;
		else if(preg_match('/^\'(.*)\'::/',$defaultValue,$matches))
			$this->defaultValue=$this->typecast(str_replace("''","'",$matches[1]));
		else if(preg_match('/^-?\d+(\.\d*)?$/',$defaultValue,$matches))
			$this->defaultValue=$this->typecast($defaultValue);
		// else is null
	}
}
