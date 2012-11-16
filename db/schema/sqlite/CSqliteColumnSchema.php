<?php
/**
 * Файл класса CSqliteColumnSchema.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Класс CSqliteColumnSchema описывает метаданные столбца таблицы базы данных SQLite.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package system.db.schema.sqlite
 * @since 1.0
 */
class CSqliteColumnSchema extends CDbColumnSchema
{
	/**
	 * Устанавливает значение столбца по умолчанию.
	 * Проходит преобразование типа для правильности типа в PHP-скриптах
	 * @param mixed $defaultValue значение столбца по умолчанию, полученное из метаданных
	 */
	protected function extractDefault($defaultValue)
	{
		$this->defaultValue=$this->typecast(strcasecmp($defaultValue,'null') ? $defaultValue : null);

		if($this->type==='string' && $this->defaultValue!==null) // PHP 5.2.6 adds single quotes while 5.2.0 doesn't
			$this->defaultValue=trim($this->defaultValue,"'\"");
	}
}
