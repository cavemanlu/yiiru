<?php
/**
 * Файл класса COciColumnSchema.
 *
 * @author Ricardo Grana <rickgrana@yahoo.com.br>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Класс COciColumnSchema описывает метаданные столбца таблицы базы данных Oracle.
 *
 * @author Ricardo Grana <rickgrana@yahoo.com.br>
 * @version $Id: COciColumnSchema.php 3515 2011-12-28 12:29:24Z mdomba $
 * @package system.db.schema.oci
 */
class COciColumnSchema extends CDbColumnSchema
{
	/**
	 * Устанавливает тип столбца в PHP-скриптах по типу в БД Oracle
	 * @param string $dbType тип столбца в БД
	 * @return string
	 */
	protected function extractOraType($dbType){
		if(strpos($dbType,'FLOAT')!==false) return 'double';

		if (strpos($dbType,'NUMBER')!==false || strpos($dbType,'INTEGER')!==false)
		{
			if(strpos($dbType,'(') && preg_match('/\((.*)\)/',$dbType,$matches))
			{
				$values=explode(',',$matches[1]);
				if(isset($values[1]) and (((int)$values[1]) > 0))
					return 'double';
				else
					return 'integer';
			}
			else
				return 'double';
		}
		else
			return 'string';
	}

	/**
	 * Устанавливает тип столбца в PHP-скриптах по типу в БД
	 * @param string $dbType тип столбца в БД
	 */
	protected function extractType($dbType)
	{
		$this->type=$this->extractOraType($dbType);
	}

	/**
	 * Устанавливает значение столбца по умолчанию.
	 * Проходит преобразование типа для правильности типа в PHP-скриптах
	 * @param mixed $defaultValue значение столбца по умолчанию, полученное из метаданных
	 */
	protected function extractDefault($defaultValue)
	{
		if(stripos($defaultValue,'timestamp')!==false)
			$this->defaultValue=null;
		else
			parent::extractDefault($defaultValue);
	}
}
