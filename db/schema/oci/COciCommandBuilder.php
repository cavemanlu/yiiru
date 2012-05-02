<?php
/**
 * Файл класса COciCommandBuilder.
 *
 * @author Ricardo Grana <rickgrana@yahoo.com.br>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Класс COciCommandBuilder предоставляет базовые методы для создания команд запросов для таблиц базы данных Oracle.
 *
 * @author Ricardo Grana <rickgrana@yahoo.com.br>
 * @version $Id: COciCommandBuilder.php 3515 2011-12-28 12:29:24Z mdomba $
 * @package system.db.schema.oci
 */
class COciCommandBuilder extends CDbCommandBuilder
{
	/**
	 * @var integer идентификатор последней вставленной строки
	 */
	public $returnID;

	/**
	 * Возвращает идентификатор (ID) последней вставленной строки переданной таблицы
	 * @param mixed $table схема таблицы ({@link CDbTableSchema}) или имя таблицы (строка)
	 * @return mixed идентификатор (ID) последней вставленной строки. Если нет имени
	 * последовательности (sequence), возвращается значение null
	 */
	public function getLastInsertID($table)
	{
		return $this->returnID;
	}

	/**
	 * Изменяет SQL-выражение, добавляя операторы LIMIT и OFFSET (при необходимости).
	 * Реализация по умолчанию применима к СУБД PostgreSQL, MySQL и SQLite
	 * @param string $sql SQL-выражение без операторов LIMIT и OFFSET
	 * @param integer $limit максимальное количество строк, -1 для игнорирования ограничения
	 * @param integer $offset смещение строк, -1 для игнорирования смещения (т.е., брать строки с первой)
	 * @return string измененное SQL-выражение
	 */
	public function applyLimit($sql,$limit,$offset)
	{
		if (($limit < 0) and ($offset < 0)) return $sql;

		$filters = array();
		if($offset>0){
			$filters[] = 'rowNumId > '.(int)$offset;
		}

		if($limit>=0){
			$filters[]= 'rownum <= '.(int)$limit;
		}

		if (count($filters) > 0){
			$filter = implode(' and ', $filters);
			$filter= " WHERE ".$filter;
		}else{
			$filter = '';
		}


		$sql = <<<EOD
				WITH USER_SQL AS ({$sql}),
				   PAGINATION AS (SELECT USER_SQL.*, rownum as rowNumId FROM USER_SQL)
				SELECT *
				FROM PAGINATION
				{$filter}
EOD;

		return $sql;
	}

	/**
	 * Создает команду INSERT для отдельной таблицы
	 * @param mixed $table схема таблицы ({@link CDbTableSchema}) или имя таблицы (строка)
	 * @param array $data вставляемые данные (имя столбца => значение столбца). Если ключ не является верным
	 * именем столбца, соответствующее значение игнорируется
	 * @return CDbCommand команда вставки
	 */
	public function createInsertCommand($table,$data)
	{
		$this->ensureTable($table);
		$fields=array();
		$values=array();
		$placeholders=array();
		$i=0;
		foreach($data as $name=>$value)
		{
			if(($column=$table->getColumn($name))!==null && ($value!==null || $column->allowNull))
			{
				$fields[]=$column->rawName;
				if($value instanceof CDbExpression)
				{
					$placeholders[]=$value->expression;
					foreach($value->params as $n=>$v)
						$values[$n]=$v;
				}
				else
				{
					$placeholders[]=self::PARAM_PREFIX.$i;
					$values[self::PARAM_PREFIX.$i]=$column->typecast($value);
					$i++;
				}
			}
		}

		$sql="INSERT INTO {$table->rawName} (".implode(', ',$fields).') VALUES ('.implode(', ',$placeholders).')';

		if(is_string($table->primaryKey) && ($column=$table->getColumn($table->primaryKey))!==null && $column->type!=='string')
		{
			$sql.=' RETURNING '.$column->rawName.' INTO :RETURN_ID';
			$command=$this->getDbConnection()->createCommand($sql);
			$command->bindParam(':RETURN_ID', $this->returnID, PDO::PARAM_INT, 12);
			$table->sequenceName='RETURN_ID';
		}
		else
			$command=$this->getDbConnection()->createCommand($sql);

		foreach($values as $name=>$value)
			$command->bindValue($name,$value);

		return $command;
	}
}