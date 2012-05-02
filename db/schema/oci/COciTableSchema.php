<?php
/**
 * Файл класса COciTableSchema.
 *
 * @author Ricardo Grana <rickgrana@yahoo.com.br>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Класс COciTableSchema представляет метаданные таблицы базы данных Oracle.
 *
 * @author Ricardo Grana <rickgrana@yahoo.com.br>
 * @version $Id: COciTableSchema.php 3515 2011-12-28 12:29:24Z mdomba $
 * @package system.db.schema.oci
 */
class COciTableSchema extends CDbTableSchema
{
	/**
	 * @var string имя схемы (базы данных), к которой относится данная таблица.
	 * По умолчанию - null, т.е., схемы нет (текущая база данных)
	 */
	public $schemaName;
}
