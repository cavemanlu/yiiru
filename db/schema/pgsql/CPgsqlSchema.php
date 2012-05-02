<?php
/**
 * Файл класса CPgsqlSchema.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Класс CPgsqlSchema - это класс для получения метаинформации БД PostgreSQL.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CPgsqlSchema.php 3592 2012-02-17 21:44:35Z qiang.xue@gmail.com $
 * @package system.db.schema.pgsql
 * @since 1.0
 */
class CPgsqlSchema extends CDbSchema
{
	const DEFAULT_SCHEMA='public';

	/**
	 * @var array массив-карта абстрактных типов столбцов в физические типы столбцов
	 * @since 1.1.6
	 */
    public $columnTypes=array(
        'pk' => 'serial NOT NULL PRIMARY KEY',
        'string' => 'character varying (255)',
        'text' => 'text',
        'integer' => 'integer',
        'float' => 'double precision',
        'decimal' => 'numeric',
        'datetime' => 'timestamp',
        'timestamp' => 'timestamp',
        'time' => 'time',
        'date' => 'date',
        'binary' => 'bytea',
        'boolean' => 'boolean',
		'money' => 'decimal(19,4)',
    );

	private $_sequences=array();

	/**
	 * Заключает в кавычки простое имя таблицы для использования в запросе.
	 * Простое имя таблицы не содержит префикса схемы
	 * @param string $name имя таблицы
	 * @return string заключенное в кавычки имя таблицы
	 * @since 1.1.6
	 */
	public function quoteSimpleTableName($name)
	{
		return '"'.$name.'"';
	}

	/**
	 * Сбрасывает значение последовательности первичного ключа таблицы.
	 * Последовательность будет сброшена таким образом, что первичный ключ следующей добавляемой строки
	 * будет иметь определенное значение или 1
	 * @param CDbTableSchema $table схема таблицы, чья последовательность первичного ключа будет сброшена
	 * @param mixed $value значение первичного ключа следующей вставленной строки. Если не задано, первичный
	 * ключ следующей строки будет иметь значение 1
	 * @since 1.1
	 */
	public function resetSequence($table,$value=null)
	{
		if($table->sequenceName!==null)
		{
			$seq='"'.$table->sequenceName.'"';
			if(strpos($seq,'.')!==false)
				$seq=str_replace('.','"."',$seq);
			if($value===null)
				$value="(SELECT COALESCE(MAX(\"{$table->primaryKey}\"),0) FROM {$table->rawName}) + 1";
			else
				$value=(int)$value;
			$this->getDbConnection()->createCommand("SELECT SETVAL('$seq', $value, false)")->execute();
		}
	}

	/**
	 * Включает или отключает проверку целостности
	 * @param boolean $check включить или выключить проверку целостности
	 * @param string $schema схема таблиц. По умолчанию - пустая строка, т.е., используется схема по умолчанию
	 * @since 1.1
	 */
	public function checkIntegrity($check=true,$schema='')
	{
		$enable=$check ? 'ENABLE' : 'DISABLE';
		$tableNames=$this->getTableNames($schema);
		$db=$this->getDbConnection();
		foreach($tableNames as $tableName)
		{
			$tableName='"'.$tableName.'"';
			if(strpos($tableName,'.')!==false)
				$tableName=str_replace('.','"."',$tableName);
			$db->createCommand("ALTER TABLE $tableName $enable TRIGGER ALL")->execute();
		}
	}

	/**
	 * Загружает метаданные определенной таблицы
	 * @param string $name имя таблицы
	 * @return CPgsqlTableSchema метаданные таблицы базы данных PostgreSQL; null, если таблица не существует
	 */
	protected function loadTable($name)
	{
		$table=new CPgsqlTableSchema;
		$this->resolveTableNames($table,$name);
		if(!$this->findColumns($table))
			return null;
		$this->findConstraints($table);

		if(is_string($table->primaryKey) && isset($this->_sequences[$table->rawName.'.'.$table->primaryKey]))
			$table->sequenceName=$this->_sequences[$table->rawName.'.'.$table->primaryKey];
		else if(is_array($table->primaryKey))
		{
			foreach($table->primaryKey as $pk)
			{
				if(isset($this->_sequences[$table->rawName.'.'.$pk]))
				{
					$table->sequenceName=$this->_sequences[$table->rawName.'.'.$pk];
					break;
				}
			}
		}

		return $table;
	}

	/**
	 * Генерирует различные виды имен таблицы
	 * @param CMysqlTableSchema $table экземпляр таблицы
	 * @param string $name не заключенныое в кавычки имя таблицы
	 */
	protected function resolveTableNames($table,$name)
	{
		$parts=explode('.',str_replace('"','',$name));
		if(isset($parts[1]))
		{
			$schemaName=$parts[0];
			$tableName=$parts[1];
		}
		else
		{
			$schemaName=self::DEFAULT_SCHEMA;
			$tableName=$parts[0];
		}

		$table->name=$tableName;
		$table->schemaName=$schemaName;
		if($schemaName===self::DEFAULT_SCHEMA)
			$table->rawName=$this->quoteTableName($tableName);
		else
			$table->rawName=$this->quoteTableName($schemaName).'.'.$this->quoteTableName($tableName);
	}

	/**
	 * Собирает метаданные столбцов таблицы
	 * @param CDbTableSchema $table метаданные таблицы
	 * @return boolean существует ли таблица в базе данных
	 */
	protected function findColumns($table)
	{
		$sql=<<<EOD
SELECT a.attname, LOWER(format_type(a.atttypid, a.atttypmod)) AS type, d.adsrc, a.attnotnull, a.atthasdef
FROM pg_attribute a LEFT JOIN pg_attrdef d ON a.attrelid = d.adrelid AND a.attnum = d.adnum
WHERE a.attnum > 0 AND NOT a.attisdropped
	AND a.attrelid = (SELECT oid FROM pg_catalog.pg_class WHERE relname=:table
		AND relnamespace = (SELECT oid FROM pg_catalog.pg_namespace WHERE nspname = :schema))
ORDER BY a.attnum
EOD;
		$command=$this->getDbConnection()->createCommand($sql);
		$command->bindValue(':table',$table->name);
		$command->bindValue(':schema',$table->schemaName);

		if(($columns=$command->queryAll())===array())
			return false;

		foreach($columns as $column)
		{
			$c=$this->createColumn($column);
			$table->columns[$c->name]=$c;

			if(stripos($column['adsrc'],'nextval')===0 && preg_match('/nextval\([^\']*\'([^\']+)\'[^\)]*\)/i',$column['adsrc'],$matches))
			{
				if(strpos($matches[1],'.')!==false || $table->schemaName===self::DEFAULT_SCHEMA)
					$this->_sequences[$table->rawName.'.'.$c->name]=$matches[1];
				else
					$this->_sequences[$table->rawName.'.'.$c->name]=$table->schemaName.'.'.$matches[1];
				$c->autoIncrement=true;
			}
		}
		return true;
	}

	/**
	 * Создает столбец таблицы
	 * @param array $column метаданные столбца
	 * @return CDbColumnSchema нормализованные метаданные столбца
	 */
	protected function createColumn($column)
	{
		$c=new CPgsqlColumnSchema;
		$c->name=$column['attname'];
		$c->rawName=$this->quoteColumnName($c->name);
		$c->allowNull=!$column['attnotnull'];
		$c->isPrimaryKey=false;
		$c->isForeignKey=false;

		$c->init($column['type'],$column['atthasdef'] ? $column['adsrc'] : null);

		return $c;
	}

	/**
	 * Собирает информацию о внешних ключах переданной таблицы
	 * @param CDbTableSchema $table метаданные таблицы
	 */
	protected function findConstraints($table)
	{
		$sql=<<<EOD
SELECT conname, consrc, contype, indkey FROM (
	SELECT
		conname,
		CASE WHEN contype='f' THEN
			pg_catalog.pg_get_constraintdef(oid)
		ELSE
			'CHECK (' || consrc || ')'
		END AS consrc,
		contype,
		conrelid AS relid,
		NULL AS indkey
	FROM
		pg_catalog.pg_constraint
	WHERE
		contype IN ('f', 'c')
	UNION ALL
	SELECT
		pc.relname,
		NULL,
		CASE WHEN indisprimary THEN
				'p'
		ELSE
				'u'
		END,
		pi.indrelid,
		indkey
	FROM
		pg_catalog.pg_class pc,
		pg_catalog.pg_index pi
	WHERE
		pc.oid=pi.indexrelid
		AND EXISTS (
			SELECT 1 FROM pg_catalog.pg_depend d JOIN pg_catalog.pg_constraint c
			ON (d.refclassid = c.tableoid AND d.refobjid = c.oid)
			WHERE d.classid = pc.tableoid AND d.objid = pc.oid AND d.deptype = 'i' AND c.contype IN ('u', 'p')
	)
) AS sub
WHERE relid = (SELECT oid FROM pg_catalog.pg_class WHERE relname=:table
	AND relnamespace = (SELECT oid FROM pg_catalog.pg_namespace
	WHERE nspname=:schema))
EOD;
		$command=$this->getDbConnection()->createCommand($sql);
		$command->bindValue(':table',$table->name);
		$command->bindValue(':schema',$table->schemaName);
		foreach($command->queryAll() as $row)
		{
			if($row['contype']==='p') // primary key
				$this->findPrimaryKey($table,$row['indkey']);
			else if($row['contype']==='f') // foreign key
				$this->findForeignKey($table,$row['consrc']);
		}
	}

	/**
	 * Собирает информацию о первичном ключе
	 * @param CPgsqlTableSchema $table метаданные таблицы
	 * @param string $indices индексный список первичных ключей pgsql
	 */
	protected function findPrimaryKey($table,$indices)
	{
		$indices=implode(', ',preg_split('/\s+/',$indices));
		$sql=<<<EOD
SELECT attnum, attname FROM pg_catalog.pg_attribute WHERE
	attrelid=(
		SELECT oid FROM pg_catalog.pg_class WHERE relname=:table AND relnamespace=(
			SELECT oid FROM pg_catalog.pg_namespace WHERE nspname=:schema
		)
	)
    AND attnum IN ({$indices})
EOD;
		$command=$this->getDbConnection()->createCommand($sql);
		$command->bindValue(':table',$table->name);
		$command->bindValue(':schema',$table->schemaName);
		foreach($command->queryAll() as $row)
		{
			$name=$row['attname'];
			if(isset($table->columns[$name]))
			{
				$table->columns[$name]->isPrimaryKey=true;
				if($table->primaryKey===null)
					$table->primaryKey=$name;
				else if(is_string($table->primaryKey))
					$table->primaryKey=array($table->primaryKey,$name);
				else
					$table->primaryKey[]=$name;
			}
		}
	}

	/**
	 * Собирает информацию о внешнем ключе
	 * @param CPgsqlTableSchema $table метаданные таблицы
	 * @param string $src определение внешнего ключа pgsql
	 */
	protected function findForeignKey($table,$src)
	{
		$matches=array();
		$brackets='\(([^\)]+)\)';
		$pattern="/FOREIGN\s+KEY\s+{$brackets}\s+REFERENCES\s+([^\(]+){$brackets}/i";
		if(preg_match($pattern,str_replace('"','',$src),$matches))
		{
			$keys=preg_split('/,\s+/', $matches[1]);
			$tableName=$matches[2];
			$fkeys=preg_split('/,\s+/', $matches[3]);
			foreach($keys as $i=>$key)
			{
				$table->foreignKeys[$key]=array($tableName,$fkeys[$i]);
				if(isset($table->columns[$key]))
					$table->columns[$key]->isForeignKey=true;
			}
		}
	}

	/**
	 * Возвращает имена всех таблиц базы данных
	 * @param string $schema схема таблиц. По умолчанию - пустая строка, т.е. текущая схема или
	 * схема по умолчанию. Если не пусто, возвращаемые имена таблиц будут с префиксом в виде имени схемы
	 * @return array имена всех таблиц базы данных
	 */
	protected function findTableNames($schema='')
	{
		if($schema==='')
			$schema=self::DEFAULT_SCHEMA;
		$sql=<<<EOD
SELECT table_name, table_schema FROM information_schema.tables
WHERE table_schema=:schema AND table_type='BASE TABLE'
EOD;
		$command=$this->getDbConnection()->createCommand($sql);
		$command->bindParam(':schema',$schema);
		$rows=$command->queryAll();
		$names=array();
		foreach($rows as $row)
		{
			if($schema===self::DEFAULT_SCHEMA)
				$names[]=$row['table_name'];
			else
				$names[]=$row['table_schema'].'.'.$row['table_name'];
		}
		return $names;
	}

	/**
	 * Создает SQL-выражение для переименования таблицы базы данных
	 * @param string $table переименуемая таблица. Имя будет заключено в кавычки
	 * @param string $newName новое имя таблицы. Имя будет заключено в кавычки
	 * @return string SQL-выражение для переименования таблицы базы данных
	 * @since 1.1.6
	 */
	public function renameTable($table, $newName)
	{
		return 'ALTER TABLE ' . $this->quoteTableName($table) . ' RENAME TO ' . $this->quoteTableName($newName);
	}

	/**
	 * Создает SQL-выражение для добавления нового столбца в таблицу
	 * @param string $table таблица, в которую добавляется столбец. Имя будет заключено в кавычки
	 * @param string $column имя нового столбца. Имя будет заключено в кавычки
	 * @param string $type тип столбца. Для конвертации абстрактного типа столбца в цизический тип
	 * будет вызван метод {@link getColumnType}. Все нераспознанное как абстрактный тип будет оставлено
	 * в сгенерированном SQL-выражении. Например, тип 'string' будет преобразован в 'varchar(255)', а
	 * 'string not null' станет 'varchar(255) not null'
	 * @return string SQL-выражение для добавления нового столбца
	 * @since 1.1.6
	 */
	public function addColumn($table, $column, $type)
	{
		$type=$this->getColumnType($type);
		$sql='ALTER TABLE ' . $this->quoteTableName($table)
			. ' ADD COLUMN ' . $this->quoteColumnName($column) . ' '
			. $this->getColumnType($type);
		return $sql;
	}

	/**
	 * Создает SQL-выражение для изменения определения столбца таблицы
	 * @param string $table таблица, столбец которой будет изменен. Имя будет заключено в кавычки
	 * @param string $column имя изменяемого столбца. Имя будет заключено в кавычки
	 * @param string $type новый тип столбца. Для конвертации абстрактного типа столбца в цизический тип
	 * будет вызван метод {@link getColumnType}. Все нераспознанное как абстрактный тип будет оставлено
	 * в сгенерированном SQL-выражении. Например, тип 'string' будет преобразован в 'varchar(255)', а
	 * 'string not null' станет 'varchar(255) not null'
	 * @return string SQL-выражение для изменения определения столбца таблицы
	 * @since 1.1.6
	 */
	public function alterColumn($table, $column, $type)
	{
		$type=$this->getColumnType($type);
		$sql='ALTER TABLE ' . $this->quoteTableName($table) . ' ALTER COLUMN '
			. $this->quoteColumnName($column) . ' TYPE ' . $this->getColumnType($type);
		return $sql;
	}

	/**
	 * Создает SQL-выражение для удаления индекса
	 * @param string $name имя удаляемого индекса. Имя будет заключено в кавычки
	 * @param string $table таблица, индекс которой будет удален. Имя будет заключено в кавычки
	 * @return string SQL-выражение для удаления индекса
	 * @since 1.1.6
	 */
	public function dropIndex($name, $table)
	{
		return 'DROP INDEX '.$this->quoteTableName($name);
	}
}
