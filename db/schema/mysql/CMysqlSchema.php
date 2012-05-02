<?php
/**
 * Файл класса CMysqlSchema.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Класс CMysqlSchema - это класс для получения метаинформации БД MySQL (версий 4.1.x и 5.x).
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CMysqlSchema.php 3515 2011-12-28 12:29:24Z mdomba $
 * @package system.db.schema.mysql
 * @since 1.0
 */
class CMysqlSchema extends CDbSchema
{
	/**
	 * @var array массив-карта абстрактных типов столбцов в физические типы столбцов
	 * @since 1.1.6
	 */
    public $columnTypes=array(
        'pk' => 'int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY',
        'string' => 'varchar(255)',
        'text' => 'text',
        'integer' => 'int(11)',
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
	 * Заключает в кавычки простое имя таблицы для использования в запросе.
	 * Простое имя таблицы не содержит префикса схемы
	 * @param string $name имя таблицы
	 * @return string заключенное в кавычки имя таблицы
	 * @since 1.1.6
	 */
	public function quoteSimpleTableName($name)
	{
		return '`'.$name.'`';
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
		return '`'.$name.'`';
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
			if($value===null)
				$value=$this->getDbConnection()->createCommand("SELECT MAX(`{$table->primaryKey}`) FROM {$table->rawName}")->queryScalar()+1;
			else
				$value=(int)$value;
			$this->getDbConnection()->createCommand("ALTER TABLE {$table->rawName} AUTO_INCREMENT=$value")->execute();
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
		$this->getDbConnection()->createCommand('SET FOREIGN_KEY_CHECKS='.($check?1:0))->execute();
	}

	/**
	 * Загружает метаданные определенной таблицы
	 * @param string $name имя таблицы
	 * @return CMysqlTableSchema метаданные таблицы базы данных MySQL; null, если таблица не существует
	 */
	protected function loadTable($name)
	{
		$table=new CMysqlTableSchema;
		$this->resolveTableNames($table,$name);

		if($this->findColumns($table))
		{
			$this->findConstraints($table);
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
		$parts=explode('.',str_replace('`','',$name));
		if(isset($parts[1]))
		{
			$table->schemaName=$parts[0];
			$table->name=$parts[1];
			$table->rawName=$this->quoteTableName($table->schemaName).'.'.$this->quoteTableName($table->name);
		}
		else
		{
			$table->name=$parts[0];
			$table->rawName=$this->quoteTableName($table->name);
		}
	}

	/**
	 * Собирает метаданные столбцов таблицы
	 * @param CDbTableSchema $table метаданные таблицы
	 * @return boolean существует ли таблица в базе данных
	 */
	protected function findColumns($table)
	{
		$sql='SHOW COLUMNS FROM '.$table->rawName;
		try
		{
			$columns=$this->getDbConnection()->createCommand($sql)->queryAll();
		}
		catch(Exception $e)
		{
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
				if($c->autoIncrement)
					$table->sequenceName='';
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
		$c=new CMysqlColumnSchema;
		$c->name=$column['Field'];
		$c->rawName=$this->quoteColumnName($c->name);
		$c->allowNull=$column['Null']==='YES';
		$c->isPrimaryKey=strpos($column['Key'],'PRI')!==false;
		$c->isForeignKey=false;
		$c->init($column['Type'],$column['Default']);
		$c->autoIncrement=strpos(strtolower($column['Extra']),'auto_increment')!==false;

		return $c;
	}

	/**
	 * @return float версия сервера
	 */
	protected function getServerVersion()
	{
		$version=$this->getDbConnection()->getAttribute(PDO::ATTR_SERVER_VERSION);
		$digits=array();
		preg_match('/(\d+)\.(\d+)\.(\d+)/', $version, $digits);
		return floatval($digits[1].'.'.$digits[2].$digits[3]);
	}

	/**
	 * Собирает информацию о внешних ключах переданной таблицы
	 * @param CDbTableSchema $table метаданные таблицы
	 */
	protected function findConstraints($table)
	{
		$row=$this->getDbConnection()->createCommand('SHOW CREATE TABLE '.$table->rawName)->queryRow();
		$matches=array();
		$regexp='/FOREIGN KEY\s+\(([^\)]+)\)\s+REFERENCES\s+([^\(^\s]+)\s*\(([^\)]+)\)/mi';
		foreach($row as $sql)
		{
			if(preg_match_all($regexp,$sql,$matches,PREG_SET_ORDER))
				break;
		}
		foreach($matches as $match)
		{
			$keys=array_map('trim',explode(',',str_replace('`','',$match[1])));
			$fks=array_map('trim',explode(',',str_replace('`','',$match[3])));
			foreach($keys as $k=>$name)
			{
				$table->foreignKeys[$name]=array(str_replace('`','',$match[2]),$fks[$k]);
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
			return $this->getDbConnection()->createCommand('SHOW TABLES')->queryColumn();
		$names=$this->getDbConnection()->createCommand('SHOW TABLES FROM '.$this->quoteTableName($schema))->queryColumn();
		foreach($names as &$name)
			$name=$schema.'.'.$name;
		return $names;
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
		$db=$this->getDbConnection();
		$row=$db->createCommand('SHOW CREATE TABLE '.$db->quoteTableName($table))->queryRow();
		if($row===false)
			throw new CDbException(Yii::t('yii','Unable to find "{column}" in table "{table}".',array('{column}'=>$name,'{table}'=>$table)));
		if(isset($row['Create Table']))
			$sql=$row['Create Table'];
		else
		{
			$row=array_values($row);
			$sql=$row[1];
		}
		if(preg_match_all('/^\s*`(.*?)`\s+(.*?),?$/m',$sql,$matches))
		{
			foreach($matches[1] as $i=>$c)
			{
				if($c===$name)
				{
					return "ALTER TABLE ".$db->quoteTableName($table)
						. " CHANGE ".$db->quoteColumnName($name)
						. ' '.$db->quoteColumnName($newName).' '.$matches[2][$i];
				}
			}
		}

		// try to give back a SQL anyway
		return "ALTER TABLE ".$db->quoteTableName($table)
			. " CHANGE ".$db->quoteColumnName($name).' '.$newName;
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
			.' DROP FOREIGN KEY '.$this->quoteColumnName($name);
	}
}
