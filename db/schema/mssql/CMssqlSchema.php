<?php
/**
 * Файл класса CMssqlSchema.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @author Christophe Boulain <Christophe.Boulain@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Класс CMssqlSchema - это класс для получения метаинформации БД MS SQL Server.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @author Christophe Boulain <Christophe.Boulain@gmail.com>
 * @version $Id: CMssqlSchema.php 3515 2011-12-28 12:29:24Z mdomba $
 * @package system.db.schema.mssql
 */
class CMssqlSchema extends CDbSchema
{
	const DEFAULT_SCHEMA='dbo';

	/**
	 * @var array массив-карта абстрактных типов столбцов в физические типы столбцов
	 * @since 1.1.6
	 */
    public $columnTypes=array(
        'pk' => 'int IDENTITY PRIMARY KEY',
        'string' => 'varchar(255)',
        'text' => 'text',
        'integer' => 'int',
        'float' => 'float',
        'decimal' => 'decimal',
        'datetime' => 'datetime',
        'timestamp' => 'timestamp',
        'time' => 'time',
        'date' => 'date',
        'binary' => 'binary',
        'boolean' => 'bit',
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
		return '['.$name.']';
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
		return '['.$name.']';
	}

	/**
	 * Сравнивает имена двух таблиц. Имена таблиц могут быть заключены в кавычки
	 * или быть без кавычек. Метод учитывает оба варианта
	 * @param string $name1 имя первой таблицы
	 * @param string $name2 имя второй таблицы
	 * @return boolean ссылаются ли имена двух таблиц на одну и ту же таблицу
	 */
	public function compareTableNames($name1,$name2)
	{
		$name1=str_replace(array('[',']'),'',$name1);
		$name2=str_replace(array('[',']'),'',$name2);
		return parent::compareTableNames(strtolower($name1),strtolower($name2));
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
			$db=$this->getDbConnection();
			if($value===null)
				$value=$db->createCommand("SELECT MAX(`{$table->primaryKey}`) FROM {$table->rawName}")->queryScalar();
			$value=(int)$value;
			$name=strtr($table->rawName,array('['=>'',']'=>''));
			$db->createCommand("DBCC CHECKIDENT ('$name', RESEED, $value)")->execute();
		}
	}

	private $_normalTables=array();  // non-view tables
	/**
	 * Включает или отключает проверку целостности
	 * @param boolean $check включить или выключить проверку целостности
	 * @param string $schema схема таблиц. По умолчанию - пустая строка, т.е., используется схема по умолчанию
	 * @since 1.1.6
	 */
	public function checkIntegrity($check=true,$schema='')
	{
		$enable=$check ? 'CHECK' : 'NOCHECK';
		if(!isset($this->_normalTables[$schema]))
			$this->_normalTables[$schema]=$this->findTableNames($schema,false);
		$db=$this->getDbConnection();
		foreach($this->_normalTables[$schema] as $tableName)
		{
			$tableName=$this->quoteTableName($tableName);
			$db->createCommand("ALTER TABLE $tableName $enable CONSTRAINT ALL")->execute();
		}
	}

	/**
	 * Загружает метаданные определенной таблицы
	 * @param string $name имя таблицы
	 * @return CMssqlTableSchema метаданные таблицы базы данных MSSQL; null, если таблица не существует
	 */
	protected function loadTable($name)
	{
		$table=new CMssqlTableSchema;
		$this->resolveTableNames($table,$name);
		//if (!in_array($table->name, $this->tableNames)) return null;
		$table->primaryKey=$this->findPrimaryKey($table);
		$table->foreignKeys=$this->findForeignKeys($table);
		if($this->findColumns($table))
		{
			return $table;
		}
		else
			return null;
	}

	/**
	 * Генерирует различные виды имен таблицы
	 * @param CMysqlTableSchema $table экземпляр таблицы
	 * @param string $name не заключенныое в кавычки имя таблицы
	 */
	protected function resolveTableNames($table,$name)
	{
		$parts=explode('.',str_replace(array('[',']'),'',$name));
		if(($c=count($parts))==3)
		{
			// Catalog name, schema name and table name provided
			$table->catalogName=$parts[0];
			$table->schemaName=$parts[1];
			$table->name=$parts[2];
			$table->rawName=$this->quoteTableName($table->catalogName).'.'.$this->quoteTableName($table->schemaName).'.'.$this->quoteTableName($table->name);
		}
		elseif ($c==2)
		{
			// Only schema name and table name provided
			$table->name=$parts[1];
			$table->schemaName=$parts[0];
			$table->rawName=$this->quoteTableName($table->schemaName).'.'.$this->quoteTableName($table->name);
		}
		else
		{
			// Only the name given, we need to get at least the schema name
			//if (empty($this->_schemaNames)) $this->findTableNames();
			$table->name=$parts[0];
			$table->schemaName=self::DEFAULT_SCHEMA;
			$table->rawName=$this->quoteTableName($table->schemaName).'.'.$this->quoteTableName($table->name);
		}
	}

	/**
	 * Получает детальную информацию о столбце(ах) первичного ключа переданной таблицы
	 * @param CMssqlTableSchema $table таблица
	 * @return mixed первичный(е) ключ(и) (null, если первичного ключа нет; строка, если
	 * первичный ключ из одного столбца; массив, если первичный ключ - составной)
	 */
	protected function findPrimaryKey($table)
	{
		$kcu='INFORMATION_SCHEMA.KEY_COLUMN_USAGE';
		$tc='INFORMATION_SCHEMA.TABLE_CONSTRAINTS';
		if (isset($table->catalogName))
		{
			$kcu=$table->catalogName.'.'.$kcu;
			$tc=$table->catalogName.'.'.$tc;
		}

		$sql = <<<EOD
		SELECT k.column_name field_name
			FROM {$this->quoteTableName($kcu)} k
		    LEFT JOIN {$this->quoteTableName($tc)} c
		      ON k.table_name = c.table_name
		     AND k.constraint_name = c.constraint_name
		   WHERE c.constraint_type ='PRIMARY KEY'
		   	    AND k.table_name = :table
				AND k.table_schema = :schema
EOD;
		$command = $this->getDbConnection()->createCommand($sql);
		$command->bindValue(':table', $table->name);
		$command->bindValue(':schema', $table->schemaName);
		$primary=$command->queryColumn();
		switch (count($primary))
		{
			case 0: // No primary key on table
				$primary=null;
				break;
			case 1: // Only 1 primary key
				$primary=$primary[0];
				break;
		}
		return $primary;
	}

	/**
	 * Получает информацию о внешних ключах переданной таблицы
	 * @param CMssqlTableSchema $table таблица
	 * @return array внешний(е) ключ(и) таблицы
	 */
	protected function findForeignKeys($table)
	{
		$rc='INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS';
		$kcu='INFORMATION_SCHEMA.KEY_COLUMN_USAGE';
		if (isset($table->catalogName))
		{
			$kcu=$table->catalogName.'.'.$kcu;
			$rc=$table->catalogName.'.'.$rc;
		}

		//From http://msdn2.microsoft.com/en-us/library/aa175805(SQL.80).aspx
		$sql = <<<EOD
		SELECT
		     KCU1.CONSTRAINT_NAME AS 'FK_CONSTRAINT_NAME'
		   , KCU1.TABLE_NAME AS 'FK_TABLE_NAME'
		   , KCU1.COLUMN_NAME AS 'FK_COLUMN_NAME'
		   , KCU1.ORDINAL_POSITION AS 'FK_ORDINAL_POSITION'
		   , KCU2.CONSTRAINT_NAME AS 'UQ_CONSTRAINT_NAME'
		   , KCU2.TABLE_NAME AS 'UQ_TABLE_NAME'
		   , KCU2.COLUMN_NAME AS 'UQ_COLUMN_NAME'
		   , KCU2.ORDINAL_POSITION AS 'UQ_ORDINAL_POSITION'
		FROM {$this->quoteTableName($rc)} RC
		JOIN {$this->quoteTableName($kcu)} KCU1
		ON KCU1.CONSTRAINT_CATALOG = RC.CONSTRAINT_CATALOG
		   AND KCU1.CONSTRAINT_SCHEMA = RC.CONSTRAINT_SCHEMA
		   AND KCU1.CONSTRAINT_NAME = RC.CONSTRAINT_NAME
		JOIN {$this->quoteTableName($kcu)} KCU2
		ON KCU2.CONSTRAINT_CATALOG =
		RC.UNIQUE_CONSTRAINT_CATALOG
		   AND KCU2.CONSTRAINT_SCHEMA =
		RC.UNIQUE_CONSTRAINT_SCHEMA
		   AND KCU2.CONSTRAINT_NAME =
		RC.UNIQUE_CONSTRAINT_NAME
		   AND KCU2.ORDINAL_POSITION = KCU1.ORDINAL_POSITION
		WHERE KCU1.TABLE_NAME = :table
EOD;
		$command = $this->getDbConnection()->createCommand($sql);
		$command->bindValue(':table', $table->name);
		$fkeys=array();
		foreach($command->queryAll() as $info)
		{
			$fkeys[$info['FK_COLUMN_NAME']]=array($info['UQ_TABLE_NAME'],$info['UQ_COLUMN_NAME'],);

		}
		return $fkeys;
	}


	/**
	 * Собирает метаданные столбцов таблицы
	 * @param CDbTableSchema $table метаданные таблицы
	 * @return boolean существует ли таблица в базе данных
	 */
	protected function findColumns($table)
	{
		$columnsTable="INFORMATION_SCHEMA.COLUMNS";
		$where=array();
		$where[]="TABLE_NAME='".$table->name."'";
		if (isset($table->catalogName))
		{
			$where[]="TABLE_CATALOG='".$table->catalogName."'";
			$columnsTable = $table->catalogName.'.'.$columnsTable;
		}
		if (isset($table->schemaName))
			$where[]="TABLE_SCHEMA='".$table->schemaName."'";

		$sql="SELECT *, columnproperty(object_id(table_schema+'.'+table_name), column_name, 'IsIdentity') as IsIdentity ".
			 "FROM ".$this->quoteTableName($columnsTable)." WHERE ".join(' AND ',$where);
		if (($columns=$this->getDbConnection()->createCommand($sql)->queryAll())===array())
			return false;

		foreach($columns as $column)
		{
			$c=$this->createColumn($column);
			if (is_array($table->primaryKey))
				$c->isPrimaryKey=in_array($c->name, $table->primaryKey);
			else
				$c->isPrimaryKey=strcasecmp($c->name,$table->primaryKey)===0;

			$c->isForeignKey=isset($table->foreignKeys[$c->name]);
			$table->columns[$c->name]=$c;
			if ($c->autoIncrement && $table->sequenceName===null)
				$table->sequenceName=$table->name;
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
		$c=new CMssqlColumnSchema;
		$c->name=$column['COLUMN_NAME'];
		$c->rawName=$this->quoteColumnName($c->name);
		$c->allowNull=$column['IS_NULLABLE']=='YES';
		if ($column['NUMERIC_PRECISION_RADIX']!==null)
		{
			// We have a numeric datatype
			$c->size=$c->precision=$column['NUMERIC_PRECISION']!==null?(int)$column['NUMERIC_PRECISION']:null;
			$c->scale=$column['NUMERIC_SCALE']!==null?(int)$column['NUMERIC_SCALE']:null;
		}
		elseif ($column['DATA_TYPE']=='image' || $column['DATA_TYPE']=='text')
			$c->size=$c->precision=null;
		else
			$c->size=$c->precision=($column['CHARACTER_MAXIMUM_LENGTH']!== null)?(int)$column['CHARACTER_MAXIMUM_LENGTH']:null;
		$c->autoIncrement=$column['IsIdentity']==1;

		$c->init($column['DATA_TYPE'],$column['COLUMN_DEFAULT']);
		return $c;
	}

	/**
	 * Возвращает имена всех таблиц базы данных
	 * @param string $schema схема таблиц. По умолчанию - пустая строка, т.е. текущая схема или
	 * схема по умолчанию. Если не пусто, возвращаемые имена таблиц будут с префиксом в виде имени схемы
	 * @param boolean $includeViews включать ли в результат представления. По умолчанию - true
	 * @return array имена всех таблиц базы данных
	 */
	protected function findTableNames($schema='',$includeViews=true)
	{
		if($schema==='')
			$schema=self::DEFAULT_SCHEMA;
		if($includeViews)
			$condition="TABLE_TYPE in ('BASE TABLE','VIEW')";
		else
			$condition="TABLE_TYPE='BASE TABLE'";
		$sql=<<<EOD
SELECT TABLE_NAME, TABLE_SCHEMA FROM [INFORMATION_SCHEMA].[TABLES]
WHERE TABLE_SCHEMA=:schema AND $condition
EOD;
		$command=$this->getDbConnection()->createCommand($sql);
		$command->bindParam(":schema", $schema);
		$rows=$command->queryAll();
		$names=array();
		foreach ($rows as $row)
		{
			if ($schema == self::DEFAULT_SCHEMA)
				$names[]=$row['TABLE_NAME'];
			else
				$names[]=$schema.'.'.$row['TABLE_SCHEMA'].'.'.$row['TABLE_NAME'];
		}

		return $names;
	}

	/**
	 * Создает построитель команд для базы данных
	 * @return CMssqlCommandBuilder экземпляр построителя команд
	 */
	protected function createCommandBuilder()
	{
		return new CMssqlCommandBuilder($this);
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
		return "sp_rename '$table', '$newName'";
	}

	/**
	 * Создает SQL-выражение для переименования столбца таблицы
	 * @param string $table таблица, столбец которой будет переименован. Имя будет заключено в кавычки
	 * @param string $name старое имя столбца. Имя будет заключено в кавычки
	 * @param string $newName новое имя столбца. Имя будет заключено в кавычки
	 * @return string SQL-выражение для переименования столбца таблицы
	 * @since 1.1.6
	 */
	public function renameColumn($table, $name, $newName)
	{
		return "sp_rename '$table.$name', '$newName', 'COLUMN'";
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
			. $this->quoteColumnName($column) . ' '
			. $this->getColumnType($type);
		return $sql;
	}
}
