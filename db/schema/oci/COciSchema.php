<?php
/**
 * Файл класса COciSchema.
 *
 * @author Ricardo Grana <rickgrana@yahoo.com.br>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Класс COciSchema - это класс для получения метаинформации БД Oracle.
 *
 * @property string $defaultSchema схема по умолчанию
 *
 * @author Ricardo Grana <rickgrana@yahoo.com.br>
 * @version $Id: COciSchema.php 3515 2011-12-28 12:29:24Z mdomba $
 * @package system.db.schema.oci
 */
class COciSchema extends CDbSchema
{
	private $_defaultSchema = '';

	/**
	 * @var array массив-карта абстрактных типов столбцов в физические типы столбцов
	 * @since 1.1.6
	 */
    public $columnTypes=array(
        'pk' => 'NUMBER(10) NOT NULL PRIMARY KEY',
        'string' => 'VARCHAR2(255)',
        'text' => 'CLOB',
        'integer' => 'NUMBER(10)',
        'float' => 'NUMBER',
        'decimal' => 'NUMBER',
        'datetime' => 'TIMESTAMP',
        'timestamp' => 'TIMESTAMP',
        'time' => 'TIMESTAMP',
        'date' => 'DATE',
        'binary' => 'BLOB',
        'boolean' => 'NUMBER(1)',
		'money' => 'NUMBER(19,4)',
    );

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
	 * Заключает в кавычки простое имя столбца для использования в запросе.
	 * Простое имя столбца не содержит префикса схемы
	 * @param string $name имя столбца
	 * @return string заключенное в кавычки имя столбца
	 * @since 1.1.6
	 */
	public function quoteSimpleColumnName($name)
	{
		return '"'.$name.'"';
	}

	/**
	 * Создает построитель команд для базы данных
	 * @return COciCommandBuilder экземпляр построителя команд
	 */
	protected function createCommandBuilder()
	{
		return new COciCommandBuilder($this);
	}

	/**
	 * Устанавливает имя схемы по умолчанию
     * @param string $schema схема по умолчанию
     */
    public function setDefaultSchema($schema)
    {
		$this->_defaultSchema=$schema;
    }

    /**
	 * Возвращает имя схемы по умолчанию
     * @return string схема по умолчанию
     */
    public function getDefaultSchema()
    {
		if (!strlen($this->_defaultSchema))
		{
			$this->setDefaultSchema(strtoupper($this->getDbConnection()->username));
		}

		return $this->_defaultSchema;
    }

    /**
     * @param string $table имя таблицы с опциональным именем схемы. Если имя схемы не задано, то используется имя по умолчанию
     * @return array массив вида ($schemaName,$tableName)
     */
    protected function getSchemaTableName($table)
    {
		$table = strtoupper($table);
		if(count($parts= explode('.', str_replace('"','',$table))) > 1)
			return array($parts[0], $parts[1]);
		else
			return array($this->getDefaultSchema(),$parts[0]);
    }

	/**
	 * Загружает метаданные определенной таблицы
	 * @param string $name имя таблицы
	 * @return COciTableSchema метаданные таблицы базы данных Oracle; null, если таблица не существует
	 */
	protected function loadTable($name)
	{
		$table=new COciTableSchema;
		$this->resolveTableNames($table,$name);

		if(!$this->findColumns($table))
			return null;
		$this->findConstraints($table);

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
			$schemaName=$this->getDefaultSchema();
			$tableName=$parts[0];
		}

		$table->name=$tableName;
		$table->schemaName=$schemaName;
		if($schemaName===$this->getDefaultSchema())
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
		$schemaName=$table->schemaName;
		$tableName=$table->name;

		$sql=<<<EOD
SELECT a.column_name, a.data_type ||
    case
        when data_precision is not null
            then '(' || a.data_precision ||
                    case when a.data_scale > 0 then ',' || a.data_scale else '' end
                || ')'
        when data_type = 'DATE' then ''
        when data_type = 'NUMBER' then ''
        else '(' || to_char(a.data_length) || ')'
    end as data_type,
    a.nullable, a.data_default,
    (   SELECT D.constraint_type
        FROM ALL_CONS_COLUMNS C
        inner join ALL_constraints D on D.OWNER = C.OWNER and D.constraint_name = C.constraint_name
        WHERE C.OWNER = B.OWNER
           and C.table_name = B.object_name
           and C.column_name = A.column_name
           and D.constraint_type = 'P') as Key
FROM ALL_TAB_COLUMNS A
inner join ALL_OBJECTS B ON b.owner = a.owner and ltrim(B.OBJECT_NAME) = ltrim(A.TABLE_NAME)
WHERE
    a.owner = '{$schemaName}'
	and (b.object_type = 'TABLE' or b.object_type = 'VIEW')
	and b.object_name = '{$tableName}'
ORDER by a.column_id
EOD;

		$command=$this->getDbConnection()->createCommand($sql);

		if(($columns=$command->queryAll())===array()){
			return false;
		}

		foreach($columns as $column)
		{
			$c=$this->createColumn($column);

			$table->columns[$c->name]=$c;
			if($c->isPrimaryKey)
			{
				if($table->primaryKey===null)
					$table->primaryKey=$c->name;
				else if(is_string($table->primaryKey))
					$table->primaryKey=array($table->primaryKey,$c->name);
				else
					$table->primaryKey[]=$c->name;
				$table->sequenceName='';
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
		$c=new COciColumnSchema;
		$c->name=$column['COLUMN_NAME'];
		$c->rawName=$this->quoteColumnName($c->name);
		$c->allowNull=$column['NULLABLE']==='Y';
		$c->isPrimaryKey=strpos($column['KEY'],'P')!==false;
		$c->isForeignKey=false;
		$c->init($column['DATA_TYPE'],$column['DATA_DEFAULT']);

		return $c;
	}

	/**
	 * Собирает информацию о внешних ключах переданной таблицы
	 * @param CDbTableSchema $table метаданные таблицы
	 */
	protected function findConstraints($table)
	{
		$sql=<<<EOD
		SELECT D.constraint_type as CONSTRAINT_TYPE, C.COLUMN_NAME, C.position, D.r_constraint_name,
                E.table_name as table_ref, f.column_name as column_ref,
            	C.table_name
        FROM ALL_CONS_COLUMNS C
        inner join ALL_constraints D on D.OWNER = C.OWNER and D.constraint_name = C.constraint_name
        left join ALL_constraints E on E.OWNER = D.r_OWNER and E.constraint_name = D.r_constraint_name
        left join ALL_cons_columns F on F.OWNER = E.OWNER and F.constraint_name = E.constraint_name and F.position = c.position
        WHERE C.OWNER = '{$table->schemaName}'
           and C.table_name = '{$table->name}'
           and D.constraint_type <> 'P'
        order by d.constraint_name, c.position
EOD;
		$command=$this->getDbConnection()->createCommand($sql);
		foreach($command->queryAll() as $row)
		{
			if($row['CONSTRAINT_TYPE']==='R')   // foreign key
			{
				$name = $row["COLUMN_NAME"];
				$table->foreignKeys[$name]=array($row["TABLE_REF"], $row["COLUMN_REF"]);
				if(isset($table->columns[$name]))
					$table->columns[$name]->isForeignKey=true;
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
		{
			$sql=<<<EOD
SELECT table_name, '{$schema}' as table_schema FROM user_tables
EOD;
			$command=$this->getDbConnection()->createCommand($sql);
		}
		else
		{
			$sql=<<<EOD
SELECT object_name as table_name, owner as table_schema FROM all_objects
WHERE object_type = 'TABLE' AND owner=:schema
EOD;
			$command=$this->getDbConnection()->createCommand($sql);
			$command->bindParam(':schema',$schema);
		}

		$rows=$command->queryAll();
		$names=array();
		foreach($rows as $row)
		{
			if($schema===$this->getDefaultSchema() || $schema==='')
				$names[]=$row['TABLE_NAME'];
			else
				$names[]=$row['TABLE_SCHEMA'].'.'.$row['TABLE_NAME'];
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
		$sql='ALTER TABLE ' . $this->quoteTableName($table) . ' MODIFY '
			. $this->quoteColumnName($column) . ' '
			. $this->getColumnType($type);
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
