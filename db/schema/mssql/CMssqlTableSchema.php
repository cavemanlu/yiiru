<?php
/**
 * Файл класса CMssqlTableSchema.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @author Christophe Boulain <Christophe.Boulain@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Класс CMssqlTableSchema представляет метаданные таблицы базы данных MSSQL.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @author Christophe Boulain <Christophe.Boulain@gmail.com>
 * @version $Id: CMssqlTableSchema.php 3515 2011-12-28 12:29:24Z mdomba $
 * @package system.db.schema.mssql
 */
class CMssqlTableSchema extends CDbTableSchema
{
	/**
	 * @var string имя каталога (базы данных), к которой относится данная таблица.
	 * По умолчанию - null, т.е., схемы нет (текущая база данных)
	 */
	public $catalogName;
	/**
	 * @var string имя схемы (базы данных), к которой относится данная таблица.
	 * По умолчанию - null, т.е., схемы нет (текущая база данных)
	 */
	public $schemaName;
}
