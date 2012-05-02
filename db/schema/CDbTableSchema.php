<?php
/**
 * Файл класса CDbTableSchema.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Класс CDbTableSchema - это базовый класс, представляющий метаданные таблицы базы данных.
 *
 * Он может быть расширен каким-либо драйвером СУБД для предоставления метаданных таблицы, специфичных для данной СУБД.
 *
 * Класс CDbTableSchema предоставляет следующую информацию о таблице:
 * <ul>
 * <li>{@link name} (имя)</li>
 * <li>{@link rawName} (исходное имя)</li>
 * <li>{@link columns} (столбцы)</li>
 * <li>{@link primaryKey} (первичный ключ)</li>
 * <li>{@link foreignKeys} (внешние ключи)</li>
 * <li>{@link sequenceName} (название последовательности)</li>
 * </ul>
 *
 * @property array $columnNames список имен столбцов
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CDbTableSchema.php 3426 2011-10-25 00:01:09Z alexander.makarow $
 * @package system.db.schema
 * @since 1.0
 */
class CDbTableSchema extends CComponent
{
	/**
	 * @var string имя таблицы
	 */
	public $name;
	/**
	 * @var string исходное имя таблицы. Это версия имени таблицы, заключенного в кавычки с опциональным
	 * именем схемы. Может быть использовано напрямую в SQL-запросах
	 */
	public $rawName;
	/**
	 * @var string|array имя первичного ключа таблицы. Если ключ составной, то имеет значение в виде массива имен ключей
	 */
	public $primaryKey;
	/**
	 * @var string название последовательности для первичного ключа. Если последовательности нет, то имеет значение null
	 */
	public $sequenceName;
	/**
	 * @var array внешние ключи таблицы. Массив, индексированный по имени столбца. Каждое значение - это массив
	 * пар 'имя внешней таблицы' => 'имя столбца связи'
	 */
	public $foreignKeys=array();
	/**
	 * @var array метаданные столбцов таблицы. Элементы массива - это объекты класса CDbColumnSchema, индексированные по именам столбцов
	 */
	public $columns=array();

	/**
	 * Возвращает метаданные столбца по его имени.
	 * Это простой метод для получения столбца по имени даже если он не существует (при этом возвращается null)
	 * @param string $name имя столбца
	 * @return CDbColumnSchema метаданные столбца. Если столбец не существует, возвращается null
	 */
	public function getColumn($name)
	{
		return isset($this->columns[$name]) ? $this->columns[$name] : null;
	}

	/**
	 * @return array список имен столбцов
	 */
	public function getColumnNames()
	{
		return array_keys($this->columns);
	}
}
