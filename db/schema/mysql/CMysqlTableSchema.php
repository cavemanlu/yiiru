<?php
/**
 * Файл класса CMysqlTableSchema.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Класс CMysqlTableSchema представляет метаданные таблицы базы данных MySQL.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CMysqlTableSchema.php 2799 2011-01-01 19:31:13Z qiang.xue $
 * @package system.db.schema.mysql
 * @since 1.0
 */
class CMysqlTableSchema extends CDbTableSchema
{
	/**
	 * @var string имя схемы (базы данных), к которой относится данная таблица.
	 * По умолчанию - null, т.е., схемы нет (текущая база данных)
	 */
	public $schemaName;
}
