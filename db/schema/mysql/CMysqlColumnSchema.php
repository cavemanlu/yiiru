<?php
/**
 * Файл класса CMysqlColumnSchema.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Класс CMysqlColumnSchema описывает метаданные столбца таблицы базы данных MySQL.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CMysqlColumnSchema.php 3204 2011-05-05 21:36:32Z alexander.makarow $
 * @package system.db.schema.mysql
 * @since 1.0
 */
class CMysqlColumnSchema extends CDbColumnSchema
{
	/**
	 * Устанавливает тип столбца в PHP-скриптах по типу в БД
	 * @param string $dbType тип столбца в БД
	 */
	protected function extractType($dbType)
	{
		if(strncmp($dbType,'enum',4)===0)
			$this->type='string';
		else if(strpos($dbType,'float')!==false || strpos($dbType,'double')!==false)
			$this->type='double';
		else if(strpos($dbType,'bool')!==false)
			$this->type='boolean';
		else if(strpos($dbType,'int')===0 && strpos($dbType,'unsigned')===false || preg_match('/(bit|tinyint|smallint|mediumint)/',$dbType))
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
		if($this->dbType==='timestamp' && $defaultValue==='CURRENT_TIMESTAMP')
			$this->defaultValue=null;
		else
			parent::extractDefault($defaultValue);
	}

	/**
	 * Устанавливает размер, точность и масштаб по типу в БД
	 * @param string $dbType тип столбца в БД
	 */
	protected function extractLimit($dbType)
	{
		if (strncmp($dbType, 'enum', 4)===0 && preg_match('/\((.*)\)/',$dbType,$matches))
		{
			$values = explode(',', $matches[1]);
			$size = 0;
			foreach($values as $value)
			{
				if(($n=strlen($value)) > $size)
					$size=$n;
			}
			$this->size = $this->precision = $size-2;
		}
		else
			parent::extractLimit($dbType);
	}
}