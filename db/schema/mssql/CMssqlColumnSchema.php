<?php
/**
 * Файл класса CMssqlColumnSchema.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @author Christophe Boulain <Christophe.Boulain@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Класс CMssqlColumnSchema описывает метаданные столбца таблицы базы данных MSSQL.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @author Christophe Boulain <Christophe.Boulain@gmail.com>
 * @version $Id: CMssqlColumnSchema.php 3515 2011-12-28 12:29:24Z mdomba $
 * @package system.db.schema.mssql
 */
class CMssqlColumnSchema extends CDbColumnSchema
{
	/**
	 * Устанавливает тип столбца в PHP-скриптах по типу в БД
	 * @param string $dbType тип столбца в БД
	 */
	protected function extractType($dbType)
	{
		if(strpos($dbType,'float')!==false || strpos($dbType,'real')!==false)
			$this->type='double';
		else if(strpos($dbType,'bigint')===false && (strpos($dbType,'int')!==false || strpos($dbType,'smallint')!==false || strpos($dbType,'tinyint')))
			$this->type='integer';
		else if(strpos($dbType,'bit')!==false)
			$this->type='boolean';
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
		if($this->dbType==='timestamp' )
			$this->defaultValue=null;
		else
			parent::extractDefault(str_replace(array('(',')',"'"), '', $defaultValue));
	}

	/**
	 * Устанавливает размер, точность и масштаб по типу в БД.
	 * Т.к. размер, точность и масштаб определены ранее, здесь ничего не делается
	 * @param string $dbType тип столбца в БД
	 */
	protected function extractLimit($dbType)
	{
	}

	/**
	 * Преобразует входное значение в тип, соответствующий типу столбца
	 * @param mixed $value входное значение
	 * @return mixed сконвертированное значение
	 */
	public function typecast($value)
	{
		if($this->type==='boolean')
			return $value ? 1 : 0;
		else
			return parent::typecast($value);
	}
}
