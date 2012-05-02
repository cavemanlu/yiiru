<?php
/**
 * Файл класса CDbSchema.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Класс CDbSchema - это базовый класс для получения метаинформации БД.
 *
 * @property CDbConnection $dbConnection соединение БД. Соединение активно
 * @property array $tables метаданные всех таблиц базы данных. Каждый элемент
 * представляет собой экземпляр класса {@link CDbTableSchema} (или его
 * потомков). Ключи массива - имена таблиц
 * @property array $tableNames имена всех таблиц базы данных
 * @property CDbCommandBuilder $commandBuilder построитель SQL-команд для данного соединения БД
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CDbSchema.php 3515 2011-12-28 12:29:24Z mdomba $
 * @package system.db.schema
 * @since 1.0
 */
abstract class CDbSchema extends CComponent
{
	/**
	 * @var array массив-карта абстрактных типов столбцов в физические типы столбцов
	 * @since 1.1.6
	 */
    public $columnTypes=array();

	private $_tableNames=array();
	private $_tables=array();
	private $_connection;
	private $_builder;
	private $_cacheExclude=array();

	/**
	 * Загружает метаданные определенной таблицы
	 * @param string $name имя таблицы
	 * @return CDbTableSchema метаданные таблицы в зависимости от драйвера БД; null, если таблица не существует
	 */
	abstract protected function loadTable($name);

	/**
	 * Конструктор
	 * @param CDbConnection $conn соединение БД
	 */
	public function __construct($conn)
	{
		$this->_connection=$conn;
		foreach($conn->schemaCachingExclude as $name)
			$this->_cacheExclude[$name]=true;
	}

	/**
	 * @return CDbConnection соединение БД. Соединение активно
	 */
	public function getDbConnection()
	{
		return $this->_connection;
	}

	/**
	 * Получает метаданные таблицы по ее имени
	 * @param string $name имя таблицы
	 * @param boolean $refresh обновлять ли кэшированную схему таблицы.
	 * Параметр доступен с версии 1.1.9
	 * @return CDbTableSchema метаданные таблицы. Null, если таблица не существует
	 */
	public function getTable($name,$refresh=false)
	{
		if($refresh===false && isset($this->_tables[$name]))
			return $this->_tables[$name];
		else
		{
			if($this->_connection->tablePrefix!==null && strpos($name,'{{')!==false)
				$realName=preg_replace('/\{\{(.*?)\}\}/',$this->_connection->tablePrefix.'$1',$name);
			else
				$realName=$name;

			// temporarily disable query caching
			if($this->_connection->queryCachingDuration>0)
			{
				$qcDuration=$this->_connection->queryCachingDuration;
				$this->_connection->queryCachingDuration=0;
			}

			if(!isset($this->_cacheExclude[$name]) && ($duration=$this->_connection->schemaCachingDuration)>0 && $this->_connection->schemaCacheID!==false && ($cache=Yii::app()->getComponent($this->_connection->schemaCacheID))!==null)
			{
				$key='yii:dbschema'.$this->_connection->connectionString.':'.$this->_connection->username.':'.$name;
				$table=$cache->get($key);
				if($refresh===true || $table===false)
				{
					$table=$this->loadTable($realName);
					if($table!==null)
						$cache->set($key,$table,$duration);
				}
				$this->_tables[$name]=$table;
			}
			else
				$this->_tables[$name]=$table=$this->loadTable($realName);

			if(isset($qcDuration))  // re-enable query caching
				$this->_connection->queryCachingDuration=$qcDuration;

			return $table;
		}
	}

	/**
	 * Возвращает метаданные всех таблиц базы данных
	 * @param string $schema схема таблиц. По умолчанию пустая строка, т.е., используется текущая схема
	 * @return array метаданные всех таблиц базы данных. Каждый элемент представляет собой
	 * экземпляр класса {@link CDbTableSchema} (или его потомков). Ключи массива - имена таблиц
	 */
	public function getTables($schema='')
	{
		$tables=array();
		foreach($this->getTableNames($schema) as $name)
		{
			if(($table=$this->getTable($name))!==null)
				$tables[$name]=$table;
		}
		return $tables;
	}

	/**
	 * Возвращает имена всех таблиц базы данных
	 * @param string $schema схема таблиц. По умолчанию пустая строка, т.е., используется текущая схема.
	 * Если не пусто, возвращенные имена таблиц будут с префиксом, равным имени схемы
	 * @return array имена всех таблиц базы данных
	 */
	public function getTableNames($schema='')
	{
		if(!isset($this->_tableNames[$schema]))
			$this->_tableNames[$schema]=$this->findTableNames($schema);
		return $this->_tableNames[$schema];
	}

	/**
	 * @return CDbCommandBuilder построитель SQL-команд для данного соединения БД
	 */
	public function getCommandBuilder()
	{
		if($this->_builder!==null)
			return $this->_builder;
		else
			return $this->_builder=$this->createCommandBuilder();
	}

	/**
	 * Обновляет схему. Данный метод сбрасывает загружденные метаданные таблиц и
	 * построитель комманд, что позволяет пересоздать метаданные и построитель
	 * для отражения изменений схемы
	 */
	public function refresh()
	{
		if(($duration=$this->_connection->schemaCachingDuration)>0 && $this->_connection->schemaCacheID!==false && ($cache=Yii::app()->getComponent($this->_connection->schemaCacheID))!==null)
		{
			foreach(array_keys($this->_tables) as $name)
			{
				if(!isset($this->_cacheExclude[$name]))
				{
					$key='yii:dbschema'.$this->_connection->connectionString.':'.$this->_connection->username.':'.$name;
					$cache->delete($key);
				}
			}
		}
		$this->_tables=array();
		$this->_tableNames=array();
		$this->_builder=null;
	}

	/**
	 * Заключает имя таблицы в кавычки для использования в запросе.
	 * Если имя таблицы содержит префикс схемы, то префикс также будет заключен в кавычки
	 * @param string $name имя таблицы
	 * @return string заключенное в кавычки имя таблицы
	 * @see quoteSimpleTableName
	 */
	public function quoteTableName($name)
	{
		if(strpos($name,'.')===false)
			return $this->quoteSimpleTableName($name);
		$parts=explode('.',$name);
		foreach($parts as $i=>$part)
			$parts[$i]=$this->quoteSimpleTableName($part);
		return implode('.',$parts);

	}

	/**
	 * Заключает в кавычки простое имя таблицы для использования в запросе.
	 * Простое имя таблицы не содержит префикса схемы
	 * @param string $name имя таблицы
	 * @return string заключенное в кавычки имя таблицы
	 * @since 1.1.6
	 */
	public function quoteSimpleTableName($name)
	{
		return "'".$name."'";
	}

	/**
	 * Заключает в кавычки имя столбца для использования в запросе.
	 * Если имя столбца содержит префикс схемы, то префикс также будет заключен в кавычки
	 * @param string $name имя столбца
	 * @return string заключенное в кавычки имя столбца
	 * @see quoteSimpleColumnName
	 */
	public function quoteColumnName($name)
	{
		if(($pos=strrpos($name,'.'))!==false)
		{
			$prefix=$this->quoteTableName(substr($name,0,$pos)).'.';
			$name=substr($name,$pos+1);
		}
		else
			$prefix='';
		return $prefix . ($name==='*' ? $name : $this->quoteSimpleColumnName($name));
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
	 * Сравнивает имена двух таблиц. Имена таблиц могут быть заключены в кавычки
	 * или быть без кавычек. Метод учитывает оба варианта
	 * @param string $name1 имя первой таблицы
	 * @param string $name2 имя второй таблицы
	 * @return boolean ссылаются ли имена двух таблиц на одну и ту же таблицу
	 */
	public function compareTableNames($name1,$name2)
	{
		$name1=str_replace(array('"','`',"'"),'',$name1);
		$name2=str_replace(array('"','`',"'"),'',$name2);
		if(($pos=strrpos($name1,'.'))!==false)
			$name1=substr($name1,$pos+1);
		if(($pos=strrpos($name2,'.'))!==false)
			$name2=substr($name2,$pos+1);
		if($this->_connection->tablePrefix!==null)
		{
			if(strpos($name1,'{')!==false)
				$name1=$this->_connection->tablePrefix.str_replace(array('{','}'),'',$name1);
			if(strpos($name2,'{')!==false)
				$name2=$this->_connection->tablePrefix.str_replace(array('{','}'),'',$name2);
		}
		return $name1===$name2;
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
	}

	/**
	 * Включает или отключает проверку целостности
	 * @param boolean $check включить или выключить проверку целостности
	 * @param string $schema схема таблиц. По умолчанию - пустая строка, т.е., используется схема по умолчанию
	 * @since 1.1
	 */
	public function checkIntegrity($check=true,$schema='')
	{
	}

	/**
	 * Создает построитель команд для базы данных.
	 * Данный метод может быть переопределен классами-потомками для создания специфичного для СУБД построителя команд
	 * @return CDbCommandBuilder экземпляр построителя команд
	 */
	protected function createCommandBuilder()
	{
		return new CDbCommandBuilder($this);
	}

	/**
	 * Возвращает имена всех таблиц базы данных. Данный метод должен быть переопределен
	 * классами-потомками для поддержки данного функционала, т.к. реализация по умолчанию
	 * просто вызывает исключение
	 * @param string $schema схема таблиц. По умолчанию - пустая строка, т.е. текущая схема или
	 * схема по умолчанию. Если не пусто, возвращаемые имена таблиц будут с префиксом в виде имени схемы
	 * @return array имена всех таблиц базы данных
	 */
	protected function findTableNames($schema='')
	{
		throw new CDbException(Yii::t('yii','{class} does not support fetching all table names.',
			array('{class}'=>get_class($this))));
	}

	/**
	 * Конвертирует абстрактный тип столбца в физический тип столбца.
	 * Конвертация происходит с использованием карты типов, определенной свойством {@link columnTypes}.
	 * Поддерживаются данные абстрактные типы столбцов (используется СУБД MySQL в качестве примера,
	 * объясняющего соответствующие физические типы таблиц:
	 * <ul>
	 * <li>pk: тип автоинкрементного первичного ключа, конвертируется в "int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY"</li>
	 * <li>string: строковый тип, конвертируется в "varchar(255)"</li>
	 * <li>text: тип длинной строки, конвертируется в "text"</li>
	 * <li>integer: целочисленный тип, конвертируется в "int(11)"</li>
	 * <li>boolean: булев тип, конвертируется в "tinyint(1)"</li>
	 * <li>float: тип числа с плавающей точкой, конвертируется в "float"</li>
	 * <li>decimal: тип десятичного числа, конвертируется в "decimal"</li>
	 * <li>datetime: тип даты и времени, конвертируется в "datetime"</li>
	 * <li>timestamp: тип временной отметки, конвертируется в "timestamp"</li>
	 * <li>time: тип времени, конвертируется в "time"</li>
	 * <li>date: тип даты, конвертируется в "date"</li>
	 * <li>binary: тип двоичных данных, конвертируется в "blob"</li>
	 * </ul>
	 *
	 * Если абстрактный тип содержит две или больше частей, разделенных пробелами (например, "string NOT NULL"),
	 * то только первая часть будет сконвертирована, а оставшаяся часть будет добавлена к результату конвертации.
	 * Например, 'string NOT NULL' сконвертируется в 'varchar(255) NOT NULL'
	 * @param string $type абстрактный тип столбца
	 * @return string физический тип столбца
	 * @since 1.1.6
	 */
    public function getColumnType($type)
    {
    	if(isset($this->columnTypes[$type]))
    		return $this->columnTypes[$type];
    	else if(($pos=strpos($type,' '))!==false)
    	{
    		$t=substr($type,0,$pos);
    		return (isset($this->columnTypes[$t]) ? $this->columnTypes[$t] : $t).substr($type,$pos);
    	}
    	else
    		return $type;
    }

	/**
	 * Создает SQL-выражение для создания новой таблицы в базе данных.
	 *
	 * Столбцы новой таблицы должны быть определены в виде пар имя=>строка ('name'=>'string'),
	 * где 'name' - имя столбца, которое будет заключено в кавычки, а 'string' - тип столбца,
	 * который может содержать абстрактный тип базы данных. Метод {@link getColumnType}
	 * будет вызван для конвертации абстрактных типов в физические.
	 *
	 * Если столбец определен без имени (например, 'PRIMARY KEY (name, type)'), то это определение
	 * будет непосредственно добавлено в генерируемое SQL-выражение
	 *
	 * @param string $table имя создаваемой таблицы. Имя будет заключено в кавычки
	 * @param array $columns столбцы (имя=>определение) новой таблицы
	 * @param string $options дополнительный SQL-фрагмент, который будет добавлен в генерируемое SQL-выражение
	 * @return string SQL-выражение для создания новой таблицы базы данных
	 * @since 1.1.6
	 */
	public function createTable($table, $columns, $options=null)
	{
		$cols=array();
		foreach($columns as $name=>$type)
		{
			if(is_string($name))
				$cols[]="\t".$this->quoteColumnName($name).' '.$this->getColumnType($type);
			else
				$cols[]="\t".$type;
		}
		$sql="CREATE TABLE ".$this->quoteTableName($table)." (\n".implode(",\n",$cols)."\n)";
		return $options===null ? $sql : $sql.' '.$options;
	}

	/**
	 * Создает SQL-выражение для переименования таблицы базы данных
	 * @param string $table переименовываемая таблица. Имя будет заключено в кавычки
	 * @param string $newName новое имя таблицы. Имя будет заключено в кавычки
	 * @return string SQL-выражение для переименования таблицы базы данных
	 * @since 1.1.6
	 */
	public function renameTable($table, $newName)
	{
		return 'RENAME TABLE ' . $this->quoteTableName($table) . ' TO ' . $this->quoteTableName($newName);
	}

	/**
	 * Создает SQL-выражение для удаления таблицы из базы данных
	 * @param string $table удаляемая таблица. Имя будет заключено в кавычки
	 * @return string SQL-выражение для удаления таблицы из базы данных
	 * @since 1.1.6
	 */
	public function dropTable($table)
	{
		return "DROP TABLE ".$this->quoteTableName($table);
	}

	/**
	 * Создает SQL-выражение для очистки таблицы базы данных
	 * @param string $table очищаемая таблица. Имя будет заключено в кавычки
	 * @return string SQL-выражение для очистки таблицы базы данных
	 * @since 1.1.6
	 */
	public function truncateTable($table)
	{
		return "TRUNCATE TABLE ".$this->quoteTableName($table);
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
		return 'ALTER TABLE ' . $this->quoteTableName($table)
			. ' ADD ' . $this->quoteColumnName($column) . ' '
			. $this->getColumnType($type);
	}

	/**
	 * Создает SQL-выражение для удаления столбца таблицы
	 * @param string $table таблица, столбец которой будет удален. Имя будет заключено в кавычки
	 * @param string $column имя удаляемого столбца. Имя будет заключено в кавычки
	 * @return string SQL-выражение для удаления столбца таблицы
	 * @since 1.1.6
	 */
	public function dropColumn($table, $column)
	{
		return "ALTER TABLE ".$this->quoteTableName($table)
			." DROP COLUMN ".$this->quoteColumnName($column);
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
		return "ALTER TABLE ".$this->quoteTableName($table)
			. " RENAME COLUMN ".$this->quoteColumnName($name)
			. " TO ".$this->quoteColumnName($newName);
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
		return 'ALTER TABLE ' . $this->quoteTableName($table) . ' CHANGE '
			. $this->quoteColumnName($column) . ' '
			. $this->quoteColumnName($column) . ' '
			. $this->getColumnType($type);
	}

	/**
	 * Создает SQL-выражение для добавления внешнего ключа в существующую таблицу. Имена
	 * таблицы и столбца будут заключены в кавычки
	 * @param string $name имя внешнего ключа
	 * @param string $table таблица, к которой будет добавлен внешний ключ
	 * @param string $columns имя столбца, для которого будет добавлено ограничение. Если это несколько
	 * столбцов, то они должны быть разделены запятыми
	 * @param string $refTable таблица, на которую ссылается внешний ключ
	 * @param string $refColumns имя столбца, на который ссылается внешний ключ. Если это несколько
	 * столбцов, то они должны быть разделены запятыми
	 * @param string $delete опция ON DELETE. Большинство СУБД поддерживают данные опции: RESTRICT, CASCADE, NO ACTION, SET DEFAULT, SET NULL
	 * @param string $update опция ON UPDATE. Большинство СУБД поддерживают данные опции: RESTRICT, CASCADE, NO ACTION, SET DEFAULT, SET NULL
	 * @return string SQL-выражение для добавления внешнего ключа в существующую таблицу
	 * @since 1.1.6
	 */
	public function addForeignKey($name, $table, $columns, $refTable, $refColumns, $delete=null, $update=null)
	{
		$columns=preg_split('/\s*,\s*/',$columns,-1,PREG_SPLIT_NO_EMPTY);
		foreach($columns as $i=>$col)
			$columns[$i]=$this->quoteColumnName($col);
		$refColumns=preg_split('/\s*,\s*/',$refColumns,-1,PREG_SPLIT_NO_EMPTY);
		foreach($refColumns as $i=>$col)
			$refColumns[$i]=$this->quoteColumnName($col);
		$sql='ALTER TABLE '.$this->quoteTableName($table)
			.' ADD CONSTRAINT '.$this->quoteColumnName($name)
			.' FOREIGN KEY ('.implode(', ', $columns).')'
			.' REFERENCES '.$this->quoteTableName($refTable)
			.' ('.implode(', ', $refColumns).')';
		if($delete!==null)
			$sql.=' ON DELETE '.$delete;
		if($update!==null)
			$sql.=' ON UPDATE '.$update;
		return $sql;
	}

	/**
	 * Создает SQL-выражение для удаления внешнего ключа
	 * @param string $name имя удаляемого внешнего ключа. Имя будет заключено в кавычки
	 * @param string $table таблица, внешний ключ которой будет удален. Имя будет заключено в кавычки
	 * @return string SQL-выражение для удаления внешнего ключа
	 * @since 1.1.6
	 */
	public function dropForeignKey($name, $table)
	{
		return 'ALTER TABLE '.$this->quoteTableName($table)
			.' DROP CONSTRAINT '.$this->quoteColumnName($name);
	}

	/**
	 * Создает SQL-выражение для создания нового индекса
	 * @param string $name имя создаваемого индекса. Имя будет заключено в
	 * кавычки
	 * @param string $table таблица, для которой будет создан новый индекс. Имя
	 * будет заключено в кавычки
	 * @param string $column столбец (или столбцы), который должен быть включен
	 * в индекс. Если это несколько
	 * столбцов, то они должны быть разделены запятыми. Имена столбцов будут
	 * заключены в кавычки, если в имени столбца присутствует скобка
	 * @param boolean $unique добавлять ли условие UNIQUE для создаваемого
	 * индекса
	 * @return string SQL-выражение для создания нового индекса
	 * @since 1.1.6
	 */
	public function createIndex($name, $table, $column, $unique=false)
	{
		$cols=array();
		$columns=preg_split('/\s*,\s*/',$column,-1,PREG_SPLIT_NO_EMPTY);
		foreach($columns as $col)
		{
			if(strpos($col,'(')!==false)
				$cols[]=$col;
			else
				$cols[]=$this->quoteColumnName($col);
		}
		return ($unique ? 'CREATE UNIQUE INDEX ' : 'CREATE INDEX ')
			. $this->quoteTableName($name).' ON '
			. $this->quoteTableName($table).' ('.implode(', ',$cols).')';
	}

	/**
	 * Создает SQL-выражение для удаления индекса
	 * @param string $name имя удаляемого индекса. Имя будет заключено в
	 * кавычки
	 * @param string $table таблица, индекс которой будет удален. Имя будет
	 * заключено в кавычки
	 * @return string SQL-выражение для удаления индекса
	 * @since 1.1.6
	 */
	public function dropIndex($name, $table)
	{
		return 'DROP INDEX '.$this->quoteTableName($name).' ON '.$this->quoteTableName($table);
	}
}
