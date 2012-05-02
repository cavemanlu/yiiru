<?php
/**
 * Файл класса CDbDataReader.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Класс CDbDataReader представляет однонаправленный поток строк результата выполнения запроса.
 *
 * Для чтения текущей строки данных надо вызвать метод {@link read}. Метод {@link readAll}
 * возвращает все строки в одном массиве.
 *
 * Также можно получить строки с использованием foreach:
 * <pre>
 * foreach($reader as $row)
 *     // $row представляет строку данных
 * </pre>
 * Т.к. CDbDataReader - однонаправленный поток данных, то его можно пройти только один раз.
 *
 * Возможно использование специфичных режимов получения данных установкой свойства
 * {@link setFetchMode FetchMode}. Обратитесь к {@link http://www.php.net/manual/en/function.PDOStatement-setFetchMode.php}
 * за деталями.
 *
 * @property boolean $isClosed закрыт ли ридер
 * @property integer $rowCount количество строк в результате запроса
 * @property integer $columnCount количество столбцов в результате запроса
 * @property mixed $fetchMode режим чтения данных
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CDbDataReader.php 3426 2011-10-25 00:01:09Z alexander.makarow $
 * @package system.db
 * @since 1.0
 */
class CDbDataReader extends CComponent implements Iterator, Countable
{
	private $_statement;
	private $_closed=false;
	private $_row;
	private $_index=-1;

	/**
	 * Конструктор
	 * @param CDbCommand $command команда, генерирующая результат запроса
	 */
	public function __construct(CDbCommand $command)
	{
		$this->_statement=$command->getPdoStatement();
		$this->_statement->setFetchMode(PDO::FETCH_ASSOC);
	}

	/**
	 * Связывает столбец с переменной PHP. При получении строки данных соответствующее
	 * значение столбца будет присвоено переменной. Примечание: режим получения данных
	 * должен включать в себя PDO::FETCH_BOUND
	 * @param mixed $column номер столбца (индексация начинается с единицы) или его имя в
	 * в результирующем наборе. Если используется имя столбца, помните, что оно должно
	 * соответствовать имени, возвращаемом драйвером БД
	 * @param mixed $value имя переменной PHP, с которой связывается столбец
	 * @param integer $dataType тип параметра
	 * @see http://www.php.net/manual/en/function.PDOStatement-bindColumn.php
	 */
	public function bindColumn($column, &$value, $dataType=null)
	{
		if($dataType===null)
			$this->_statement->bindColumn($column,$value);
		else
			$this->_statement->bindColumn($column,$value,$dataType);
	}

	/**
	 * Устанавливает режим чтения по умолчанию для данного состояния
	 * @param mixed $mode fetch mode
	 * @see http://www.php.net/manual/en/function.PDOStatement-setFetchMode.php
	 */
	public function setFetchMode($mode)
	{
		$params=func_get_args();
		call_user_func_array(array($this->_statement,'setFetchMode'),$params);
	}

	/**
	 * Сдвигает ридер к следующей строке результирующего набора
	 * @return array|false текущая строка; false, если строк больше нет
	 */
	public function read()
	{
		return $this->_statement->fetch();
	}

	/**
	 * Возвращает один столбец следующей строки результирующего набора (одна ячейка)
	 * @param integer $columnIndex индексированный с нуля номер столбца
	 * @return mixed|false столбец текущей строки; false, если строк больше нет
	 */
	public function readColumn($columnIndex)
	{
		return $this->_statement->fetchColumn($columnIndex);
	}

	/**
	 * Возвращает объект данных следующей строки
	 * @param string $className имя класса возвращаемого объекта
	 * @param array $fields поля возвращаемого объекта
	 * @return mixed|false объект данных; false, если строк больше нет
	 */
	public function readObject($className,$fields)
	{
		return $this->_statement->fetchObject($className,$fields);
	}

	/**
	 * Читает весь результирующий набор в массив
	 * @return array результирующий набор (каждый элемент массива представляет строку данных).
	 * Если результат не содержит строк, то возвращается пустой массив
	 */
	public function readAll()
	{
		return $this->_statement->fetchAll();
	}

	/**
	 * Перемещает ридер к следующей строке результата запроса.
	 * Метод работает только если результат запроса состоит из нескольких строк.
	 * Не все СУБД поддерживают данную функцию
	 * @return boolean true при успешном переходе на следующую строку, false - при неудаче
	 */
	public function nextResult()
	{
		if(($result=$this->_statement->nextRowset())!==false)
			$this->_index=-1;
		return $result;
	}

	/**
	 * Закрывает ридер. Освобождает ресурсы, выделенные для выполнения SQL-выражения.
	 * Попытка чтения после выполнения данного метода непредсказуема
	 */
	public function close()
	{
		$this->_statement->closeCursor();
		$this->_closed=true;
	}

	/**
	 * Закрыт ли ридер
	 * @return boolean закрыт ли ридер
	 */
	public function getIsClosed()
	{
		return $this->_closed;
	}

	/**
	 * Возвращает количество строк в результате запроса.
	 * Примечание: большинство СУБД могут не давать значимого значения.
	 * В данном случае, для получения числа строк надо использовать SQL запрос "SELECT COUNT(*) FROM tableName"
	 * @return integer количество строк в результате запроса
	 */
	public function getRowCount()
	{
		return $this->_statement->rowCount();
	}

	/**
	 * Возвращает количество строк в результате запроса.
	 * Данный метод требуется интерфейсом Countable.
	 * Примечание: большинство СУБД могут не давать значимого значения.
	 * В данном случае, для получения числа строк надо использовать SQL запрос "SELECT COUNT(*) FROM tableName"
	 * @return integer количество строк в результате запроса
	 */
	public function count()
	{
		return $this->getRowCount();
	}

	/**
	 * Возвращает количество столбцов в результате запроса.
	 * Примечание: даже если ридер не содержит строк, все равно будет возвращаться
	 * правильное количество столбцов
	 * @return integer количество столбцов в результате запроса
	 */
	public function getColumnCount()
	{
		return $this->_statement->columnCount();
	}

	/**
	 * Сбрасывает итератор к начальному значению.
	 * Данный метод требуется интерфейсом Iterator
	 * @throws CException вызывается, если метод вызван дважды
	 */
	public function rewind()
	{
		if($this->_index<0)
		{
			$this->_row=$this->_statement->fetch();
			$this->_index=0;
		}
		else
			throw new CDbException(Yii::t('yii','CDbDataReader cannot rewind. It is a forward-only reader.'));
	}

	/**
	 * Возвращает индекс текущей строки.
	 * Данный метод требуется интерфейсом Iterator
	 * @return integer индекс текущей строки
	 */
	public function key()
	{
		return $this->_index;
	}

	/**
	 * Возвращает текущую строку.
	 * Данный метод требуется интерфейсом Iterator
	 * @return mixed текущая строка
	 */
	public function current()
	{
		return $this->_row;
	}

	/**
	 * Перемещает внутренний указатель на следующую строку.
	 * Данный метод требуется интерфейсом Iterator
	 */
	public function next()
	{
		$this->_row=$this->_statement->fetch();
		$this->_index++;
	}

	/**
	 * Показывает, есть ли строка данных в текущей позиции.
	 * Данный метод требуется интерфейсом Iterator
	 * @return boolean есть ли строка данных в текущей позиции
	 */
	public function valid()
	{
		return $this->_row!==false;
	}
}
