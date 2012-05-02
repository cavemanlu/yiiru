<?php
/**
 * Файл класса CDbMigration.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Класс CDbMigration - это базовый класс для представления миграций базы данных.
 *
 * CDbMigration спроектирован для использования с командой "yiic migrate".
 *
 * Каждый класс-наследник класса CDbMigration представляет собой отдельную миграцию БД,
 * идентифицируемую именем класса-потомка.
 *
 * В каждой миграции метод {@link up} содержит логику для обновления базы данных,
 * используемой в приложении, а метод {@link down} содержит логику для отката изменений
 * в базе данных. Команда "yiic migrate" управляет всеми доступными миграциями приложения.
 *
 * CDbMigration предоставляет набор простых методов для манипулирования данными и схемой базы данных.
 * Например, метод {@link insert} может использоваться для простой вставки строки данных в
 * таблицу базы данных, а метод {@link createTable} - для создания таблицы базы данных.
 * По сравнению с аналогичными методами класса {@link CDbCommand} данные методы будут отображать
 * дополнительную информацию, показывающую параметры методов и время выполнения, что может быть полезно
 * при использовании миграции.
 *
 * @property CDbConnection $dbConnection текущее активное соединение БД
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CDbMigration.php 3514 2011-12-27 20:28:26Z alexander.makarow $
 * @package system.db
 * @since 1.1.6
 */
abstract class CDbMigration extends CComponent
{
	private $_db;

	/**
	 * Содержит логику, выполняемую при применении данной миграции.
	 * Классы-потомки могут иметь свою реализацию данного метода для предоставления
	 * необходимой логики миграции
	 * @return boolean
	 */
	public function up()
	{
		$transaction=$this->getDbConnection()->beginTransaction();
		try
		{
			if($this->safeUp()===false)
			{
				$transaction->rollBack();
				return false;
			}
			$transaction->commit();
		}
		catch(Exception $e)
		{
			echo "Exception: ".$e->getMessage().' ('.$e->getFile().':'.$e->getLine().")\n";
			echo $e->getTraceAsString()."\n";
			$transaction->rollBack();
			return false;
		}
	}

	/**
	 * Содержит логику, выполняемую при отмене данной миграции.
	 * Реализация по умолчанию вызывает исключение, показывающее, что миграция не может быть отменена.
	 * Классы-потомки могут иметь свою реализацию данного метода для предоставления
	 * необходимой логики отмены миграции
	 * @return boolean
	 */
	public function down()
	{
		$transaction=$this->getDbConnection()->beginTransaction();
		try
		{
			if($this->safeDown()===false)
			{
				$transaction->rollBack();
				return false;
			}
			$transaction->commit();
		}
		catch(Exception $e)
		{
			echo "Exception: ".$e->getMessage().' ('.$e->getFile().':'.$e->getLine().")\n";
			echo $e->getTraceAsString()."\n";
			$transaction->rollBack();
			return false;
		}
	}

	/**
	 * Содержит логику, выполняемую при применении данной миграции.
	 * Отличается от метода {@link up} тем, что логика, реализованная здесь, будет
	 * заключена в транзакцию. Классы-потомки могут реализовать данный метод 
	 * вместо метода {@link up}, если логика базы данных требует использования транзакции
	 * @return boolean
	 * @since 1.1.7
	 */
	public function safeUp()
	{
	}

	/**
	 * Содержит логику, выполняемую при отмене данной миграции.
	 * Отличается от метода {@link down} тем, что логика, реализованная здесь, будет
	 * заключена в транзакцию. Классы-потомки могут реализовать данный метод 
	 * вместо метода {@link up}, если логика базы данных требует использования транзакции
	 * @return boolean
	 * @since 1.1.7
	 */
	public function safeDown()
	{
	}

	/**
	 * Возвращает текущее активное соединение БД. По умолчанию возвращается и
	 * активируется компонент приложения 'db'. Можно вызвать метод {@link setDbConnection}
	 * для переключения на другое соединение БД. Такие методы как {@link insert} и {@link createTable}
	 * будут использовать данное соединение БД для выполнения запросов к базе данных
	 * @return CDbConnection текущее активное соединение БД
	 */
	public function getDbConnection()
	{
		if($this->_db===null)
		{
			$this->_db=Yii::app()->getComponent('db');
			if(!$this->_db instanceof CDbConnection)
				throw new CException(Yii::t('yii', 'The "db" application component must be configured to be a CDbConnection object.'));
		}
		return $this->_db;
	}

	/**
	 * Устанавливает текущее активное соединение БД. Такие методы как {@link insert} и {@link createTable}
	 * будут использовать данное соединение БД для выполнения запросов к базе данных
	 * @param CDbConnection $db компонент соединения БД
	 */
	public function setDbConnection($db)
	{
		$this->_db=$db;
	}

	/**
	 * Выполняет SQL-выражение.
	 * Данный метод выполняет определенное SQL-выраждение, используя {@link dbConnection}
	 * @param string $sql выполняемое SQL-выражение
	 * @param array $params входные параметры (имя => значение) для выполнения SQL-выражения.
	 * См. {@link CDbCommand::execute}
	 * @since 1.1.7
	 */
	public function execute($sql, $params=array())
	{
		echo "    > execute SQL: $sql ...";
		$time=microtime(true);
		$this->getDbConnection()->createCommand($sql)->execute($params);
		echo " done (time: ".sprintf('%.3f', microtime(true)-$time)."s)\n";
	}

	/**
	 * Создает и выполняет SQL-выражение INSERT.
	 * Метод заключает в кавычки имена столбцов и связывает вставляемые значения
	 * @param string $table таблица, в которую вставляются новые строки
	 * @param array $columns данные вставляемых в таблицу столбцов (имя => значение)
	 */
	public function insert($table, $columns)
	{
		echo "    > insert into $table ...";
		$time=microtime(true);
		$this->getDbConnection()->createCommand()->insert($table, $columns);
		echo " done (time: ".sprintf('%.3f', microtime(true)-$time)."s)\n";
	}

	/**
	 * Создает и выполняет SQL-выражение UPDATE.
	 * Метод заключает в кавычки имена столбцов и связывает обновляемые значения
	 * @param string $table обновляемая таблица
	 * @param array $columns обновляемые данные столбцов (имя => значения)
	 * @param mixed $conditions условие, добавляемое в выражение WHERE. См.
	 * {@link CDbCommand::where}
	 * @param array $params параметры, передаваемые в запрос
	 */
	public function update($table, $columns, $conditions='', $params=array())
	{
		echo "    > update $table ...";
		$time=microtime(true);
		$this->getDbConnection()->createCommand()->update($table, $columns, $conditions, $params);
		echo " done (time: ".sprintf('%.3f', microtime(true)-$time)."s)\n";
	}

	/**
	 * Создает и выполняет SQL-выражение DELETE
	 * @param string $table таблица, из которой удаляются данные
	 * @param mixed $conditions условие, добавляемое в выражение WHERE. См.
	 * {@link CDbCommand::where}
	 * @param array $params параметры, передаваемые в запрос
	 */
	public function delete($table, $conditions='', $params=array())
	{
		echo "    > delete from $table ...";
		$time=microtime(true);
		$this->getDbConnection()->createCommand()->delete($table, $conditions, $params);
		echo " done (time: ".sprintf('%.3f', microtime(true)-$time)."s)\n";
	}

	/**
	 * Создает и выполняет SQL-выражение для создания новой таблицы базы данных.
	 *
	 * Столбцы новой таблицы должны быть заданы в виде пар имя-определение ('имя' => 'строка'),
	 * где имя - это имя столбца, заключаемое в кавычки данным методом, а определение -
	 * тип столбца, который может содержать абстрактный тип БД. Метод
	 * {@link getColumnType} будет вызван для конвертации абстрактного типа в физический.
	 *
	 * Если столбец задан только определением (например, 'PRIMARY KEY (name, type)'), он будет
	 * непосредственно вставлен в генерируемый запрос
	 *
	 * @param string $table имя создаваемой таблицы. Имя будет заключено в кавычки
	 * @param array $columns столбцы (имя=>определение) новой таблицы
	 * @param string $options дополнительный SQL-фрагмент, который будет добавлен в генерируемое SQL-выражение
	 */
	public function createTable($table, $columns, $options=null)
	{
		echo "    > create table $table ...";
		$time=microtime(true);
		$this->getDbConnection()->createCommand()->createTable($table, $columns, $options);
		echo " done (time: ".sprintf('%.3f', microtime(true)-$time)."s)\n";
	}

	/**
	 * Создает и выполняет SQL-выражение для переименования таблицы базы данных
	 * @param string $table переименовываемая таблица. Имя будет заключено в кавычки
	 * @param string $newName новое имя таблицы. Имя будет заключено в кавычки
	 */
	public function renameTable($table, $newName)
	{
		echo "    > rename table $table to $newName ...";
		$time=microtime(true);
		$this->getDbConnection()->createCommand()->renameTable($table, $newName);
		echo " done (time: ".sprintf('%.3f', microtime(true)-$time)."s)\n";
	}

	/**
	 * Создает и выполняет SQL-выражение для удаления таблицы из базы данных
	 * @param string $table удаляемая таблица. Имя будет заключено в кавычки
	 */
	public function dropTable($table)
	{
		echo "    > drop table $table ...";
		$time=microtime(true);
		$this->getDbConnection()->createCommand()->dropTable($table);
		echo " done (time: ".sprintf('%.3f', microtime(true)-$time)."s)\n";
	}

	/**
	 * Создает и выполняет SQL-выражение для очистки таблицы базы данных
	 * @param string $table очищаемая таблица. Имя будет заключено в кавычки
	 */
	public function truncateTable($table)
	{
		echo "    > truncate table $table ...";
		$time=microtime(true);
		$this->getDbConnection()->createCommand()->truncateTable($table);
		echo " done (time: ".sprintf('%.3f', microtime(true)-$time)."s)\n";
	}

	/**
	 * Создает и выполняет SQL-выражение для добавления нового столбца в таблицу
	 * @param string $table таблица, в которую добавляется столбец. Имя будет заключено в кавычки
	 * @param string $column имя нового столбца. Имя будет заключено в кавычки
	 * @param string $type тип столбца. Для конвертации абстрактного типа столбца в цизический тип
	 * будет вызван метод {@link getColumnType}. Все нераспознанное как абстрактный тип будет оставлено
	 * в сгенерированном SQL-выражении. Например, тип 'string' будет преобразован в 'varchar(255)', а
	 * 'string not null' станет 'varchar(255) not null'
	 */
	public function addColumn($table, $column, $type)
	{
		echo "    > add column $column $type to table $table ...";
		$time=microtime(true);
		$this->getDbConnection()->createCommand()->addColumn($table, $column, $type);
		echo " done (time: ".sprintf('%.3f', microtime(true)-$time)."s)\n";
	}

	/**
	 * Создает и выполняет SQL-выражение для удаления столбца таблицы
	 * @param string $table таблица, столбец которой будет удален. Имя будет заключено в кавычки
	 * @param string $column имя удаляемого столбца. Имя будет заключено в кавычки
	 */
	public function dropColumn($table, $column)
	{
		echo "    > drop column $column from table $table ...";
		$time=microtime(true);
		$this->getDbConnection()->createCommand()->dropColumn($table, $column);
		echo " done (time: ".sprintf('%.3f', microtime(true)-$time)."s)\n";
	}

	/**
	 * Создает и выполняет SQL-выражение для переименования столбца таблицы
	 * @param string $table таблица, столбец которой будет переименован. Имя будет заключено в кавычки
	 * @param string $name старое имя столбца. Имя будет заключено в кавычки
	 * @param string $newName новое имя столбца. Имя будет заключено в кавычки
	 */
	public function renameColumn($table, $name, $newName)
	{
		echo "    > rename column $name in table $table to $newName ...";
		$time=microtime(true);
		$this->getDbConnection()->createCommand()->renameColumn($table, $name, $newName);
		echo " done (time: ".sprintf('%.3f', microtime(true)-$time)."s)\n";
	}

	/**
	 * Создает и выполняет SQL-выражение для изменения определения столбца таблицы
	 * @param string $table таблица, столбец которой будет изменен. Имя будет заключено в кавычки
	 * @param string $column имя изменяемого столбца. Имя будет заключено в кавычки
	 * @param string $type новый тип столбца. Для конвертации абстрактного типа столбца в цизический тип
	 * будет вызван метод {@link getColumnType}. Все нераспознанное как абстрактный тип будет оставлено
	 * в сгенерированном SQL-выражении. Например, тип 'string' будет преобразован в 'varchar(255)', а
	 * 'string not null' станет 'varchar(255) not null'
	 */
	public function alterColumn($table, $column, $type)
	{
		echo "    > alter column $column in table $table to $type ...";
		$time=microtime(true);
		$this->getDbConnection()->createCommand()->alterColumn($table, $column, $type);
		echo " done (time: ".sprintf('%.3f', microtime(true)-$time)."s)\n";
	}

	/**
	 * Создает и выполняет SQL-выражение для добавления внешнего ключа в существующую таблицу. Имена
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
	 */
	public function addForeignKey($name, $table, $columns, $refTable, $refColumns, $delete=null, $update=null)
	{
		echo "    > add foreign key $name: $table ($columns) references $refTable ($refColumns) ...";
		$time=microtime(true);
		$this->getDbConnection()->createCommand()->addForeignKey($name, $table, $columns, $refTable, $refColumns, $delete, $update);
		echo " done (time: ".sprintf('%.3f', microtime(true)-$time)."s)\n";
	}

	/**
	 * Создает и выполняет SQL-выражение для удаления внешнего ключа
	 * @param string $name имя удаляемого внешнего ключа. Имя будет заключено в кавычки
	 * @param string $table таблица, внешний ключ которой будет удален. Имя будет заключено в кавычки
	 */
	public function dropForeignKey($name, $table)
	{
		echo "    > drop foreign key $name from table $table ...";
		$time=microtime(true);
		$this->getDbConnection()->createCommand()->dropForeignKey($name, $table);
		echo " done (time: ".sprintf('%.3f', microtime(true)-$time)."s)\n";
	}

	/**
	 * Создает и выполняет SQL-выражение для создания нового индекса
	 * @param string $name имя создаваемого индекса. Имя будет заключено в кавычки
	 * @param string $table таблица, для которой будет создан новый индекс. Имя будет заключено в кавычки
	 * @param string $column столбец (или столбцы), который должен быть включен в индекс. Если это несколько
	 * столбцов, то они должны быть разделены запятыми. Имена столбцов будут заключены в кавычки
	 * @param boolean $unique добавлять ли условие UNIQUE для создаваемого индекса
	 */
	public function createIndex($name, $table, $column, $unique=false)
	{
		echo "    > create".($unique ? ' unique':'')." index $name on $table ($column) ...";
		$time=microtime(true);
		$this->getDbConnection()->createCommand()->createIndex($name, $table, $column, $unique);
		echo " done (time: ".sprintf('%.3f', microtime(true)-$time)."s)\n";
	}

	/**
	 * Создает и выполняет SQL-выражение для удаления индекса
	 * @param string $name имя удаляемого индекса. Имя будет заключено в кавычки
	 * @param string $table таблица, индекс которой будет удален. Имя будет заключено в кавычки
	 */
	public function dropIndex($name, $table)
	{
		echo "    > drop index $name ...";
		$time=microtime(true);
		$this->getDbConnection()->createCommand()->dropIndex($name, $table);
		echo " done (time: ".sprintf('%.3f', microtime(true)-$time)."s)\n";
	}

	/**
	 * Обновляет кэшированную схему таблицы
	 * @param string $table имя обновляемой таблицы
	 * @since 1.1.9
	 */
	public function refreshTableSchema($table)
	{
		echo "    > refresh table $table schema cache ...";
		$time=microtime(true);
		$this->getDbConnection()->getSchema()->getTable($table,true);
		echo " done (time: ".sprintf('%.3f', microtime(true)-$time)."s)\n";
	}
}