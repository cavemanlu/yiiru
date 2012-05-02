<?php
/**
 * Файл класса CSqliteSchema.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Класс CSqliteSchema - это класс для получения метаинформации БД SQLite (версий 2 и 3).
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CSqliteSchema.php 3515 2011-12-28 12:29:24Z mdomba $
 * @package system.db.schema.sqlite
 * @since 1.0
 */
class CSqliteSchema extends CDbSchema
{
	/**
	 * @var array массив-карта абстрактных типов столбцов в физические типы столбцов
	 * @since 1.1.6
	 */
    public $columnTypes=array(
        'pk' => 'integer PRIMARY KEY AUTOINCREMENT NOT NULL',
        'string' => 'varchar(255)',
        'text' => 'text',
        'integer' => 'integer',
        'float' => 'float',
        'decimal' => 'decimal',
        'datetime' => 'datetime',
        'timestamp' => 'timestamp',
        'time' => 'time',
        'date' => 'date',
        'binary' => 'blob',
        'boolean' => 'tinyint(1)',
		'money' => 'decimal(19,4)',
    );

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
			if($value===null)
				$value=$this->getDbConnection()->createCommand("SELECT MAX(`{$table->primaryKey}`) FROM {$table->rawName}")->queryScalar();
			else
				$value=(int)$value-1;
			try
			{
				// it's possible sqlite_sequence does not exist
				$this->getDbConnection()->createCommand("UPDATE sqlite_sequence SET seq='$value' WHERE name='{$table->name}'")->execute();
			}
			catch(Exception $e)
			{
			}
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
		// SQLite doesn't enforce integrity
		return;
	}

	/**
	 * Возвращает имена всех таблиц базы данных
	 * @param string $schema схема таблиц. Не используется для базы данных sqlite
	 * @return array имена всех таблиц базы данных
	 */
	protected function findTableNames($schema='')
	{
		$sql="SELECT DISTINCT tbl_name FROM sqlite_master WHERE tbl_name<>'sqlite_sequence'";
		return $this->getDbConnection()->createCommand($sql)->queryColumn();
	}

	/**
	 * Создает построитель команд для базы данных
	 * @return CSqliteCommandBuilder экземпляр построителя команд
	 */
	protected function createCommandBuilder()
	{
		return new CSqliteCommandBuilder($this);
	}

	/**
	 * Загружает метаданные определенной таблицы
	 * @param string $name имя таблицы
	 * @return CDbTableSchema метаданные таблицы базы данных SQLite; null, если таблица не существует
	 */
	protected function loadTable($name)
	{
		$table=new CDbTableSchema;
		$table->name=$name;
		$table->rawName=$this->quoteTableName($name);

		if($this->findColumns($table))
		{
			$this->findConstraints($table);
			return $table;
		}
		else
			return null;
	}

	/**
	 * Собирает метаданные столбцов таблицы
	 * @param CDbTableSchema $table метаданные таблицы
	 * @return boolean существует ли таблица в базе данных
	 */
	protected function findColumns($table)
	{
		$sql="PRAGMA table_info({$table->rawName})";
		$columns=$this->getDbConnection()->createCommand($sql)->queryAll();
		if(empty($columns))
			return false;

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
			}
		}
		if(is_string($table->primaryKey) && !strncasecmp($table->columns[$table->primaryKey]->dbType,'int',3))
		{
			$table->sequenceName='';
			$table->columns[$table->primaryKey]->autoIncrement=true;
		}

		return true;
	}

	/**
	 * Собирает информацию о внешних ключах переданной таблицы
	 * @param CDbTableSchema $table метаданные таблицы
	 */
	protected function findConstraints($table)
	{
		$foreignKeys=array();
		$sql="PRAGMA foreign_key_list({$table->rawName})";
		$keys=$this->getDbConnection()->createCommand($sql)->queryAll();
		foreach($keys as $key)
		{
			$column=$table->columns[$key['from']];
			$column->isForeignKey=true;
			$foreignKeys[$key['from']]=array($key['table'],$key['to']);
		}
		$table->foreignKeys=$foreignKeys;
	}

	/**
	 * Создает столбец таблицы
	 * @param array $column метаданные столбца
	 * @return CDbColumnSchema нормализованные метаданные столбца
	 */
	protected function createColumn($column)
	{
		$c=new CSqliteColumnSchema;
		$c->name=$column['name'];
		$c->rawName=$this->quoteColumnName($c->name);
		$c->allowNull=!$column['notnull'];
		$c->isPrimaryKey=$column['pk']!=0;
		$c->isForeignKey=false;
		$c->init(strtolower($column['type']),$column['dflt_value']);
		return $c;
	}

	/**
	 * Создает SQL-выражение для очистки таблицы базы данных
	 * @param string $table очищаемая таблица. Имя будет заключено в кавычки
	 * @return string SQL-выражение для очистки таблицы базы данных
	 * @since 1.1.6
	 */
	public function truncateTable($table)
	{
		return "DELETE FROM ".$this->quoteTableName($table);
	}

	/**
	 * Создает SQL-выражение для удаления столбца таблицы.
	 * @param string $table таблица, столбец которой будет удален. Имя будет заключено в кавычки
	 * @param string $column имя удаляемого столбца. Имя будет заключено в кавычки
	 * @return string SQL-выражение для удаления столбца таблицы
	 * @since 1.1.6
	 */
	public function dropColumn($table, $column)
	{
		throw new CDbException(Yii::t('yii', 'Dropping DB column is not supported by SQLite.'));
	}

	/**
	 * Создает SQL-выражение для переименования столбца таблицы.
	 * Т.к. SQLite не поддерживает переименование столбца таблицы, вызов данного метода будет вызывать исключение
	 * @param string $table таблица, столбец которой будет переименован. Имя будет заключено в кавычки
	 * @param string $name старое имя столбца. Имя будет заключено в кавычки
	 * @param string $newName новое имя столбца. Имя будет заключено в кавычки
	 * @return string SQL-выражение для переименования столбца таблицы
	 * @since 1.1.6
	 */
	public function renameColumn($table, $name, $newName)
	{
		throw new CDbException(Yii::t('yii', 'Renaming a DB column is not supported by SQLite.'));
	}

	/**
	 * Создает SQL-выражение для добавления внешнего ключа в существующую таблицу.
	 * Т.к. SQLite не поддерживает добавление внешнего ключа в существующую таблицу, вызов данного метода будет вызывать исключение
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
		throw new CDbException(Yii::t('yii', 'Adding a foreign key constraint to an existing table is not supported by SQLite.'));
	}

	/**
	 * Создает SQL-выражение для удаления внешнего ключа.
	 * Т.к. SQLite не поддерживает удаление внешнего ключа, вызов данного метода будет вызывать исключение
	 * @param string $name имя удаляемого внешнего ключа. Имя будет заключено в кавычки
	 * @param string $table таблица, внешний ключ которой будет удален. Имя будет заключено в кавычки
	 * @return string SQL-выражение для удаления внешнего ключа
	 * @since 1.1.6
	 */
	public function dropForeignKey($name, $table)
	{
		throw new CDbException(Yii::t('yii', 'Dropping a foreign key constraint is not supported by SQLite.'));
	}

	/**
	 * Создает SQL-выражение для изменения определения столбца таблицы.
	 * Т.к. SQLite не поддерживает изменение столбца существующей таблицы, вызов данного метода будет вызывать исключение
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
		throw new CDbException(Yii::t('yii', 'Altering a DB column is not supported by SQLite.'));
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
