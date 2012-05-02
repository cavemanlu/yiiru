<?php
/**
 * Файл класса CSqliteCommandBuilder.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Класс CSqliteCommandBuilder предоставляет базовые методы для создания команд запросов для таблиц базы данных SQLite.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CSqliteCommandBuilder.php 3515 2011-12-28 12:29:24Z mdomba $
 * @package system.db.schema.sqlite
 * @since 1.0
 */
class CSqliteCommandBuilder extends CDbCommandBuilder
{
	/**
	 * Генерирует SQL-выражение для выбора строк по определенным значениям композитного ключа.
	 * Данный метод переопределен, т.к. SQLite не поддерживает по умолчанию выражение IN
	 * в композитных ключах
	 * @param CDbTableSchema $table схема таблицы
	 * @param array $values список значений первичного ключа для выборки
	 * @param string $prefix префикс столбца (с точкой на конце)
	 * @return string SQL-выражение выборки
	 */
	protected function createCompositeInCondition($table,$values,$prefix)
	{
		$keyNames=array();
		foreach(array_keys($values[0]) as $name)
			$keyNames[]=$prefix.$table->columns[$name]->rawName;
		$vs=array();
		foreach($values as $value)
			$vs[]=implode("||','||",$value);
		return implode("||','||",$keyNames).' IN ('.implode(', ',$vs).')';
	}
}
