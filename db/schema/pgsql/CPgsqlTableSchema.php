<?php
/**
 * Файл класса CPgsqlTable.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Класс CPgsqlTable представляет метаданные таблицы базы данных PostgreSQL.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CPgsqlTableSchema.php 2799 2011-01-01 19:31:13Z qiang.xue $
 * @package system.db.schema.pgsql
 * @since 1.0
 */
class CPgsqlTableSchema extends CDbTableSchema
{
	/**
	 * @var string имя схемы, к которой относится данная таблица
	 */
	public $schemaName;
}
