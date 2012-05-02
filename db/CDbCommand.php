<?php
/**
 * Файл класса CDbCommand.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Класс CDbCommand представляет SQL-выражение, выполняемое в базе данных.
 *
 * Обычно создается вызовом метода {@link CDbConnection::createCommand}.
 * Выполняемое SQL-выражение может быть установлено свойством {@link setText Text}.
 *
 * Для выполнения не запросное SQL-выражение (такое, как INSERT, DELETE или UPDATE),
 * необходимо вызвать метод {@link execute}. Для выполнения SQL-выражения,
 * возвращающего результирующий набор (такое, как SELECT), вызывается метод
 * {@link query} или его простые версии {@link queryRow}, {@link queryColumn}
 * или {@link queryScalar}.
 *
 * Если SQL-выражение возвращает результирующий набор (такое, как SELECT), то
 * результаты будут доступны посредством возвращенного экземпляра класса
 * {@link CDbDataReader}.
 *
 * CDbCommand поддерживает подготовку SQL выражений и связывание параметров.
 * Для связывания переменной PHP с параметром в SQL-выражении вызывается метод
 * {@link bindParam}. Для связывания значения с параметром в SQL-выражении
 * вызывается метод {@link bindValue}. SQL-выражение автоматически
 * подготавливается при связывании параметра. Также можно вызвать метод
 * {@link prepare} для явной подготовки SQL-выражения.
 *
 * С версии 1.1.6, класс CDbCommand также может использоваться в качестве
 * построителя запросов, создающего SQL-выражения из фрагментов кода. Например,
 * <pre>
 * $user = Yii::app()->db->createCommand()
 *     ->select('username, password')
 *     ->from('tbl_user')
 *     ->where('id=:id', array(':id'=>1))
 *     ->queryRow();
 * </pre>
 *
 * @property string $text выполняемое SQL-выражение
 * @property CDbConnection $connection соединение, ассоциированное с данной
 * командой
 * @property PDOStatement $pdoStatement лежащий в основе команды экземпляр
 * PDOStatement. Может принимать значение null, если выражение еще не
 * подготовлено
 * @property string $select часть "SELECT" (без ключевого слова 'SELECT')
 * запроса
 * @property boolean $distinct значение, показывающее, должно ли использоваться
 * выражение SELECT DISTINCT
 * @property string $from часть "FROM" (без ключевого слова 'FROM') запроса
 * @property string $where часть WHERE (без ключевого слова 'WHERE') запроса
 * @property mixed $joinчасть JOIN запроса. Может быть массивом, представляющим
 * несколько соединяемых фрагментов, или строкой, представляющей один фрагмент
 * соединения. Каждый фрагмент соединения должен содержать правильный оператор
 * соединения (например, LEFT JOIN)
 * @property string $group часть GROUP BY (без ключевого слова 'GROUP BY')
 * запроса
 * @property string $having часть HAVING (без ключевого слова 'HAVING') запроса
 * @property string $order часть ORDER BY (без ключевого слова 'ORDER BY')
 * запроса
 * @property string $limit часть LIMIT (без ключевого слова 'LIMIT') запроса
 * @property string $offset часть OFFSET (без ключевого слова 'OFFSET') запроса
 * @property mixed $union часть UNION (без ключевого слова 'UNION') запроса.
 * Может быть строкой или массивом, представляющим несколько объединяемых
 * частей
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CDbCommand.php 3515 2011-12-28 12:29:24Z mdomba $
 * @package system.db
 * @since 1.0
 */
class CDbCommand extends CComponent
{
	/**
	 * @var array параметры (имя => значение), передаваемые в данный запрос
	 * @since 1.1.6
	 */
	public $params=array();

	private $_connection;
	private $_text;
	private $_statement;
	private $_paramLog=array();
	private $_query;
	private $_fetchMode = array(PDO::FETCH_ASSOC);

	/**
	 * Конструктор
	 * @param CDbConnection $connection соединение БД
	 * @param mixed $query выполняемый запрос БД. Может быть либо строкой,
	 * представляющей SQL-выражение либо массивом, пары имя-значения которого
	 * будут использоваться для установки соответствующих свойств создаваемого
	 * объекта команды.
	 *
	 * Например, можно передать либо строку <code>'SELECT * FROM tbl_user'</code>
	 * либо массив <code>array('select'=>'*', 'from'=>'tbl_user')</code>. Они
	 * эквивалентны для конечного запроса.
	 *
	 * При передаче запроса в виде массива, могут устанавливаться следующие
	 * свойства: {@link select}, {@link distinct}, {@link from}, {@link where},
	 * {@link join}, {@link group}, {@link having}, {@link order},
	 * {@link limit}, {@link offset} и {@link union}. За деталями о каждом из
	 * свойств обратитесь к установщикам (сеттерам) каждого из них. Данный
	 * функционал доступен с версии 1.1.6.
	 *
	 * С версии 1.1.7 можно использовать специфичный режим получения данных,
	 * установив свойство {@link setFetchMode FetchMode}. За деталями обратитесь
	 * к {@link http://www.php.net/manual/en/function.PDOStatement-setFetchMode.php}
 	 */
	public function __construct(CDbConnection $connection,$query=null)
	{
		$this->_connection=$connection;
		if(is_array($query))
		{
			foreach($query as $name=>$value)
				$this->$name=$value;
		}
		else
			$this->setText($query);
	}

	/**
	 * Устанавливает выражение в значение null при сериализации
	 * @return array
	 */
	public function __sleep()
	{
		$this->_statement=null;
		return array_keys(get_object_vars($this));
	}

	/**
	 * Устанавливает режим получения данных по умолчанию для данного выражения
	 * @param mixed $mode режим получения данных
	 * @return CDbCommand
	 * @see http://www.php.net/manual/en/function.PDOStatement-setFetchMode.php
	 * @since 1.1.7
	 */
	public function setFetchMode($mode)
	{
		$params=func_get_args();
		$this->_fetchMode = $params;
		return $this;
	}

	/**
	 * Очищает команду и подготавливает для создания нового запроса. Данный
	 * метод используется в случае, когда объкт команды используется несколько
	 * раз для постороения разных запросов. Вызов данного метода очищает все
	 * внутренние состояния объекта команды
	 * @return CDbCommand экземпляр команды
	 * @since 1.1.6
	 */
	public function reset()
	{
		$this->_text=null;
		$this->_query=null;
		$this->_statement=null;
		$this->_paramLog=array();
		$this->params=array();
		return $this;
	}

	/**
	 * @return string выполняемое SQL-выражение
	 */
	public function getText()
	{
		if($this->_text=='' && !empty($this->_query))
			$this->setText($this->buildQuery($this->_query));
		return $this->_text;
	}

	/**
	 * Определяет выполняемое SQL-выражение. Все предыдущие выполнения будут
	 * прекращены или отменены
	 * @param string $value выполняемое SQL-выражение
	 * @return CDbCommand экземпляр команды
	 */
	public function setText($value)
	{
		if($this->_connection->tablePrefix!==null && $value!='')
			$this->_text=preg_replace('/{{(.*?)}}/',$this->_connection->tablePrefix.'\1',$value);
		else
			$this->_text=$value;
		$this->cancel();
		return $this;
	}

	/**
	 * @return CDbConnection соединение, ассоциированное с данной командой
	 */
	public function getConnection()
	{
		return $this->_connection;
	}

	/**
	 * @return PDOStatement лежащий в основе команды экземпляр PDOStatement.
	 * Может принимать значение null, если выражение еще не подготовлено
	 */
	public function getPdoStatement()
	{
		return $this->_statement;
	}

	/**
	 * Подготоваливает SQL-выражение к выполнению. Для сложных SQL-выражений,
	 * выполняемых несколько раз, данный метод может увеличить
	 * производительность. Для SQL-выражений со связываемыми параметрами,
	 * данный метод вызывается автоматически
	 */
	public function prepare()
	{
		if($this->_statement==null)
		{
			try
			{
				$this->_statement=$this->getConnection()->getPdoInstance()->prepare($this->getText());
				$this->_paramLog=array();
			}
			catch(Exception $e)
			{
				Yii::log('Error in preparing SQL: '.$this->getText(),CLogger::LEVEL_ERROR,'system.db.CDbCommand');
                $errorInfo = $e instanceof PDOException ? $e->errorInfo : null;
				throw new CDbException(Yii::t('yii','CDbCommand failed to prepare the SQL statement: {error}',
					array('{error}'=>$e->getMessage())),(int)$e->getCode(),$errorInfo);
			}
		}
	}

	/**
	 * Отменяет выполнение SQL-выражение
	 */
	public function cancel()
	{
		$this->_statement=null;
	}

	/**
	 * Связывает параметр с выполняемым SQL-выражением
	 * @param mixed $name идентификатор параметра. Для подготовленного
	 * выражения с использованием меток является именем параметра в виде :name.
	 * Для подготовленного выражения с использованием знака вопроса является
	 * позицией параметра, индексированной с единицы
	 * @param mixed $value имя PHP-переменной, связываемой с параметром
	 * SQL-выражения
	 * @param integer $dataType SQL-тип параметра. Если равен значению null,
	 * тип определяется на основе PHP-типа значения
	 * @param integer $length длина типа
	 * @param mixed $driverOptions опции, специфичные для драйверов подключения
	 * к БД (доступен с версии 1.1.6)
	 * @return CDbCommand текущая выполняемая команда
	 * @see http://www.php.net/manual/en/function.PDOStatement-bindParam.php
	 */
	public function bindParam($name, &$value, $dataType=null, $length=null, $driverOptions=null)
	{
		$this->prepare();
		if($dataType===null)
			$this->_statement->bindParam($name,$value,$this->_connection->getPdoType(gettype($value)));
		else if($length===null)
			$this->_statement->bindParam($name,$value,$dataType);
		else if($driverOptions===null)
			$this->_statement->bindParam($name,$value,$dataType,$length);
		else
			$this->_statement->bindParam($name,$value,$dataType,$length,$driverOptions);
		$this->_paramLog[$name]=&$value;
		return $this;
	}

	/**
	 * Связывает значение с параметром
	 * @param mixed $name идентификатор параметра. Для подготовленного
	 * выражения с использованием меток является именем параметра в виде :name.
	 * Для подготовленного выражения с использованием знака вопроса является
	 * позицией параметра, индексированной с единицы
	 * @param mixed $value значение, связываемое с параметром
	 * @param integer $dataType SQL-тип параметра. Если равен значению null,
	 * тип определяется на основе PHP-типа значения
	 * @return CDbCommand текущая выполняемая команда
	 * @see http://www.php.net/manual/en/function.PDOStatement-bindValue.php
	 */
	public function bindValue($name, $value, $dataType=null)
	{
		$this->prepare();
		if($dataType===null)
			$this->_statement->bindValue($name,$value,$this->_connection->getPdoType(gettype($value)));
		else
			$this->_statement->bindValue($name,$value,$dataType);
		$this->_paramLog[$name]=$value;
		return $this;
	}

	/**
	 * Связывает список значений с соответствующими параметрами.
	 * Данный метод похож на метод {@link bindValue}, но связывает сразу
	 * несколько значений. Примечание: SQL-тип каждого значения определяется
	 * его PHP-типом
	 * @param array $values связываемые значения. Должны быть переданы в виде
	 * ассоциативного массива с ключами - именами параметров и значениями
	 * массива - соответствующими значениями параметра. Например,
	 * <code>array(':name'=>'John', ':age'=>25)</code>.
	 * @return CDbCommand текущая выполняемая команда
	 * @since 1.1.5
	 */
	public function bindValues($values)
	{
		$this->prepare();
		foreach($values as $name=>$value)
		{
			$this->_statement->bindValue($name,$value,$this->_connection->getPdoType(gettype($value)));
			$this->_paramLog[$name]=$value;
		}
		return $this;
	}

	/**
	 * Выполняет SQL-выражение. Данный метод имеет значение только для
	 * выполнения не запросных SQL-выражений (не SELECT). Результирующий набор
	 * не возвращается
	 * @param array $params входные параметры (имя => значение) для выполнения
	 * SQL-выражения. Альтернатива методам {@link bindParam} и
	 * {@link bindValue}. Если имеется несколько входных параметров, передача
	 * их данным способом может увеличить производительность. Примечание: при
	 * передаче параметров данным способом нельзя связать параметры или
	 * значения с использованием методов {@link bindParam} или
	 * {@link bindValue}, и наоборот
	 * @return integer количество строк, затронутых данным выполнением
	 * SQL-выражения
	 * @throws CException вызывается, если выполнение SQL-выражения неуспешно
	 */
	public function execute($params=array())
	{
		if($this->_connection->enableParamLogging && ($pars=array_merge($this->_paramLog,$params))!==array())
		{
			$p=array();
			foreach($pars as $name=>$value)
				$p[$name]=$name.'='.var_export($value,true);
			$par='. Bound with ' .implode(', ',$p);
		}
		else
			$par='';
		Yii::trace('Executing SQL: '.$this->getText().$par,'system.db.CDbCommand');
		try
		{
			if($this->_connection->enableProfiling)
				Yii::beginProfile('system.db.CDbCommand.execute('.$this->getText().')','system.db.CDbCommand.execute');

			$this->prepare();
			if($params===array())
				$this->_statement->execute();
			else
				$this->_statement->execute($params);
			$n=$this->_statement->rowCount();

			if($this->_connection->enableProfiling)
				Yii::endProfile('system.db.CDbCommand.execute('.$this->getText().')','system.db.CDbCommand.execute');

			return $n;
		}
		catch(Exception $e)
		{
			if($this->_connection->enableProfiling)
				Yii::endProfile('system.db.CDbCommand.execute('.$this->getText().')','system.db.CDbCommand.execute');
            $errorInfo = $e instanceof PDOException ? $e->errorInfo : null;
            $message = $e->getMessage();
			Yii::log(Yii::t('yii','CDbCommand::execute() failed: {error}. The SQL statement executed was: {sql}.',
				array('{error}'=>$message, '{sql}'=>$this->getText().$par)),CLogger::LEVEL_ERROR,'system.db.CDbCommand');
            if(YII_DEBUG)
            	$message .= '. The SQL statement executed was: '.$this->getText().$par;
			throw new CDbException(Yii::t('yii','CDbCommand failed to execute the SQL statement: {error}',
				array('{error}'=>$message)),(int)$e->getCode(),$errorInfo);
		}
	}

	/**
	 * Выполняет SQL-выражение и возвращает результат запроса. Данный метод
	 * предназначен для SQL-запросов, возвращающих результирующий набор
	 * @param array $params входные параметры (имя => значение) для выполнения
	 * SQL-выражения. Альтернатива методам {@link bindParam} и
	 * {@link bindValue}. Если имеется несколько входных параметров, передача
	 * их данным способом может увеличить производительность. Примечание: при
	 * передаче параметров данным способом нельзя связать параметры или
	 * значения с использованием методов {@link bindParam} или
	 * {@link bindValue}, и наоборот
	 * @return CDbDataReader объект ридера (reader) для получения результата
	 * запроса
	 * @throws CException вызывается, если выполнение SQL-выражения неуспешно
	 */
	public function query($params=array())
	{
		return $this->queryInternal('',0,$params);
	}

	/**
	 * Выполняет SQL-выражение и возвращает все строки
	 * @param boolean $fetchAssociative должна ли каждая строка возвращаться в
	 * виде ассоциативного массива с именами столбцов в качестве ключей массива
	 * или индексированных ключей массива (отсчет с нуля)
	 * @param array $params входные параметры (имя => значение) для выполнения
	 * SQL-выражения. Альтернатива методам {@link bindParam} и
	 * {@link bindValue}. Если имеется несколько входных параметров, передача
	 * их данным способом может увеличить производительность. Примечание: при
	 * передаче параметров данным способом нельзя связать параметры или
	 * значения с использованием методов {@link bindParam} или
	 * {@link bindValue}, и наоборот
	 * @return array все строки результата запроса. Каждый элемент массива -
	 * это массив, представляющий строку данных. Если результат запроса пустой,
	 * то будет возвращен пустой массив
	 * @throws CException вызывается, если выполнение SQL-выражения неуспешно
	 */
	public function queryAll($fetchAssociative=true,$params=array())
	{
		return $this->queryInternal('fetchAll',$fetchAssociative ? $this->_fetchMode : PDO::FETCH_NUM, $params);
	}

	/**
	 * Выполняет SQL-выражение и возвращает первую строку результата запроса.
	 * Это простая обертка метода {@link query} в случае, когда требуется
	 * получить только первую строку данных
	 * @param boolean $fetchAssociative должна ли каждая строка возвращаться в
	 * виде ассоциативного массива с именами столбцов в качестве ключей массива
	 * или индексированных ключей массива (отсчет с нуля)
	 * @param array $params входные параметры (имя => значение) для выполнения
	 * SQL-выражения. Альтернатива методам {@link bindParam} и
	 * {@link bindValue}. Если имеется несколько входных параметров, передача
	 * их данным способом может увеличить производительность. Примечание: при
	 * передаче параметров данным способом нельзя связать параметры или
	 * значения с использованием методов {@link bindParam} или
	 * {@link bindValue}, и наоборот
	 * @return mixed первая строка (в виде массива) результата запроса; false,
	 * если результат запроса пуст
	 * @throws CException вызывается, если выполнение SQL-выражения неуспешно
	 */
	public function queryRow($fetchAssociative=true,$params=array())
	{
		return $this->queryInternal('fetch',$fetchAssociative ? $this->_fetchMode : PDO::FETCH_NUM, $params);
	}

	/**
	 * Выполняет SQL-выражение и возвращает значение первого столбца первой
	 * строки данных. Это простая обертка метода {@link query} в случае, когда
	 * требуется получить только одно скалярное значение (например, получение
	 * количества строк)
	 * @param array $params входные параметры (имя => значение) для выполнения
	 * SQL-выражения. Альтернатива методам {@link bindParam} и
	 * {@link bindValue}. Если имеется несколько входных параметров, передача
	 * их данным способом может увеличить производительность. Примечание: при
	 * передаче параметров данным способом нельзя связать параметры или
	 * значения с использованием методов {@link bindParam} или
	 * {@link bindValue}, и наоборот
	 * @return mixed значение первого столбца первой строки результата запроса;
	 * false, если значения нет
	 * @throws CException вызывается, если выполнение SQL-выражения неуспешно
	 */
	public function queryScalar($params=array())
	{
		$result=$this->queryInternal('fetchColumn',0,$params);
		if(is_resource($result) && get_resource_type($result)==='stream')
			return stream_get_contents($result);
		else
			return $result;
	}

	/**
	 * Выполняет SQL-выражение и возвращает первый столбец результата запроса.
	 * Это простая обертка метода {@link query} в случае, когда требуется
	 * получить только первый столбец данных. Примечание: возвращенный столбец
	 * будет содержать первый элемент каждой строки результата запроса
	 * @param array $params входные параметры (имя => значение) для выполнения
	 * SQL-выражения. Альтернатива методам {@link bindParam} и
	 * {@link bindValue}. Если имеется несколько входных параметров, передача
	 * их данным способом может увеличить производительность. Примечание: при
	 * передаче параметров данным способом нельзя связать параметры или
	 * значения с использованием методов {@link bindParam} или
	 * {@link bindValue}, и наоборот
	 * @return array первый столбец результата запроса. Если результат пустой,
	 * то возвращается пустой массив
	 * @throws CException вызывается, если выполнение SQL-выражения неуспешно
	 */
	public function queryColumn($params=array())
	{
		return $this->queryInternal('fetchAll',PDO::FETCH_COLUMN,$params);
	}

	/**
	 * @param string $method вызываемый метод класса PDOStatement
	 * @param mixed $mode параметры, передаваемые в метод
	 * @param array $params входные параметры (имя => значение) для выполнения
	 * SQL-выражения. Альтернатива методам {@link bindParam} и
	 * {@link bindValue}. Если имеется несколько входных параметров, передача
	 * их данным способом может увеличить производительность. Примечание: при
	 * передаче параметров данным способом нельзя связать параметры или
	 * значения с использованием методов {@link bindParam} или
	 * {@link bindValue}, и наоборот
	 * @return mixed результат выполнения метода
	 */
	private function queryInternal($method,$mode,$params=array())
	{
		$params=array_merge($this->params,$params);

		if($this->_connection->enableParamLogging && ($pars=array_merge($this->_paramLog,$params))!==array())
		{
			$p=array();
			foreach($pars as $name=>$value)
				$p[$name]=$name.'='.var_export($value,true);
			$par='. Bound with '.implode(', ',$p);
		}
		else
			$par='';

		Yii::trace('Querying SQL: '.$this->getText().$par,'system.db.CDbCommand');

		if($this->_connection->queryCachingCount>0 && $method!==''
				&& $this->_connection->queryCachingDuration>0
				&& $this->_connection->queryCacheID!==false
				&& ($cache=Yii::app()->getComponent($this->_connection->queryCacheID))!==null)
		{
			$this->_connection->queryCachingCount--;
			$cacheKey='yii:dbquery'.$this->_connection->connectionString.':'.$this->_connection->username;
			$cacheKey.=':'.$this->getText().':'.serialize(array_merge($this->_paramLog,$params));
			if(($result=$cache->get($cacheKey))!==false)
			{
				Yii::trace('Query result found in cache','system.db.CDbCommand');
				return $result;
			}
		}

		try
		{
			if($this->_connection->enableProfiling)
				Yii::beginProfile('system.db.CDbCommand.query('.$this->getText().$par.')','system.db.CDbCommand.query');

			$this->prepare();
			if($params===array())
				$this->_statement->execute();
			else
				$this->_statement->execute($params);

			if($method==='')
				$result=new CDbDataReader($this);
			else
			{
				$mode=(array)$mode;
				$result=call_user_func_array(array($this->_statement, $method), $mode);
				$this->_statement->closeCursor();
			}

			if($this->_connection->enableProfiling)
				Yii::endProfile('system.db.CDbCommand.query('.$this->getText().$par.')','system.db.CDbCommand.query');

			if(isset($cache,$cacheKey))
				$cache->set($cacheKey, $result, $this->_connection->queryCachingDuration, $this->_connection->queryCachingDependency);

			return $result;
		}
		catch(Exception $e)
		{
			if($this->_connection->enableProfiling)
				Yii::endProfile('system.db.CDbCommand.query('.$this->getText().$par.')','system.db.CDbCommand.query');
            $errorInfo = $e instanceof PDOException ? $e->errorInfo : null;
            $message = $e->getMessage();
			Yii::log(Yii::t('yii','CDbCommand::{method}() failed: {error}. The SQL statement executed was: {sql}.',
				array('{method}'=>$method, '{error}'=>$message, '{sql}'=>$this->getText().$par)),CLogger::LEVEL_ERROR,'system.db.CDbCommand');
            if(YII_DEBUG)
            	$message .= '. The SQL statement executed was: '.$this->getText().$par;
			throw new CDbException(Yii::t('yii','CDbCommand failed to execute the SQL statement: {error}',
				array('{error}'=>$message)),(int)$e->getCode(),$errorInfo);
		}
	}

	/**
	 * Создает SQL-выражение SELECT по переданной спецификации запроса
	 * @param array $query спецификация запроса в виде пар имя-значение.
	 * Поддерживаются следующие опции: {@link select}, {@link distinct}, {@link from},
	 * {@link where}, {@link join}, {@link group}, {@link having}, {@link order},
	 * {@link limit}, {@link offset} и {@link union}.
	 * @return string SQL-выражение
	 * @since 1.1.6
	 */
	public function buildQuery($query)
	{
		$sql=isset($query['distinct']) && $query['distinct'] ? 'SELECT DISTINCT' : 'SELECT';
		$sql.=' '.(isset($query['select']) ? $query['select'] : '*');

		if(isset($query['from']))
			$sql.="\nFROM ".$query['from'];
		else
			throw new CDbException(Yii::t('yii','The DB query must contain the "from" portion.'));

		if(isset($query['join']))
			$sql.="\n".(is_array($query['join']) ? implode("\n",$query['join']) : $query['join']);

		if(isset($query['where']))
			$sql.="\nWHERE ".$query['where'];

		if(isset($query['group']))
			$sql.="\nGROUP BY ".$query['group'];

		if(isset($query['having']))
			$sql.="\nHAVING ".$query['having'];

		if(isset($query['order']))
			$sql.="\nORDER BY ".$query['order'];

		$limit=isset($query['limit']) ? (int)$query['limit'] : -1;
		$offset=isset($query['offset']) ? (int)$query['offset'] : -1;
		if($limit>=0 || $offset>0)
			$sql=$this->_connection->getCommandBuilder()->applyLimit($sql,$limit,$offset);

		if(isset($query['union']))
			$sql.="\nUNION (\n".(is_array($query['union']) ? implode("\n) UNION (\n",$query['union']) : $query['union']) . ')';

		return $sql;
	}

	/**
	 * Устанавливает часть "SELECT" запроса
	 * @param mixed $columns выбираемые столбцы. По умолчанию - '*', т.е.,
	 * выбираются все столбцы. Столбцы могут быть определены в виде строки
	 * (например, "id, name") или массива (например, array('id', 'name')).
	 * Столбцы могут содержать префиксы таблиц (например, "tbl_user.id") и/или
	 * псевдонимы столбцов (например, "tbl_user.id AS user_id"). Метод
	 * автоматически заключает в кавычки имена столбцов, если столбец не
	 * содержит скобок (т.е., столбец не содержит выражение БД)
	 * @param string $option дополнительные опции, добавляемые в выражение
	 * 'SELECT'. Например, в MySQL может быть использована опция
	 * 'SQL_CALC_FOUND_ROWS'. Данный параметр поддерживается с версии 1.1.8
	 * @return CDbCommand объект данной команды
	 * @since 1.1.6
	 */
	public function select($columns='*', $option='')
	{
		if(is_string($columns) && strpos($columns,'(')!==false)
			$this->_query['select']=$columns;
		else
		{
			if(!is_array($columns))
				$columns=preg_split('/\s*,\s*/',trim($columns),-1,PREG_SPLIT_NO_EMPTY);

			foreach($columns as $i=>$column)
			{
				if(is_object($column))
					$columns[$i]=(string)$column;
				else if(strpos($column,'(')===false)
				{
					if(preg_match('/^(.*?)(?i:\s+as\s+|\s+)(.*)$/',$column,$matches))
						$columns[$i]=$this->_connection->quoteColumnName($matches[1]).' AS '.$this->_connection->quoteColumnName($matches[2]);
					else
						$columns[$i]=$this->_connection->quoteColumnName($column);
				}
			}
			$this->_query['select']=implode(', ',$columns);
		}
		if($option!='')
			$this->_query['select']=$option.' '.$this->_query['select'];
		return $this;
	}

	/**
	 * Возвращает часть "SELECT" запроса
	 * @return string часть "SELECT" (без ключевого слова 'SELECT') запроса
	 * @since 1.1.6
	 */
	public function getSelect()
	{
		return isset($this->_query['select']) ? $this->_query['select'] : '';
	}

	/**
	 * Устанавливает часть "SELECT" запроса
	 * @param mixed $value выбираемые данные. За деталями спецификации данного
	 * параметра обратитесь к описанию метода {@link select()}
	 * @since 1.1.6
	 */
	public function setSelect($value)
	{
		$this->select($value);
	}

	/**
	 * Устанавливает часть "SELECT" запроса с включенным флагом DISTINCT
	 * @param mixed $columns выбираемые столбцы. За деталями спецификации данного
	 * параметра обратитесь к описанию метода {@link select()}
	 * @return CDbCommand объект данной команды
	 * @since 1.1.6
	 */
	public function selectDistinct($columns='*')
	{
		$this->_query['distinct']=true;
		return $this->select($columns);
	}

	/**
	 * Возвращает значение, показывающее, должно ли использоваться выражение
	 * SELECT DISTINCT
	 * @return boolean значение, показывающее, должно ли использоваться
	 * выражение SELECT DISTINCT
	 * @since 1.1.6
	 */
	public function getDistinct()
	{
		return isset($this->_query['distinct']) ? $this->_query['distinct'] : false;
	}

	/**
	 * Устанавливает значение, показывающее, должно ли использоваться выражение
	 * SELECT DISTINCT
	 * @param boolean $value значение, показывающее, должно ли использоваться
	 * выражение SELECT DISTINCT
	 * @since 1.1.6
	 */
	public function setDistinct($value)
	{
		$this->_query['distinct']=$value;
	}

	/**
	 * Устанавливает часть "FROM" запроса
	 * @param mixed $tables таблица(ы), из которой(ых) производится выборка.
	 * Может быть строкой (например, 'tbl_user') или массивом (например,
	 * array('tbl_user', 'tbl_profile')), определяющими одно или несколько имен
	 * таблиц. Имена таблиц могут содержать префикс схемы (например,
	 * 'public.tbl_user') и/или псевдонимы таблиц (например, 'tbl_user u').
	 * Метод автоматически заключает в кавычки имена таблиц, если они не
	 * содержат скобок (т.е., таблица передана не в виде подзапроса)
	 * @return CDbCommand объект данной команды
	 * @since 1.1.6
	 */
	public function from($tables)
	{
		if(is_string($tables) && strpos($tables,'(')!==false)
			$this->_query['from']=$tables;
		else
		{
			if(!is_array($tables))
				$tables=preg_split('/\s*,\s*/',trim($tables),-1,PREG_SPLIT_NO_EMPTY);
			foreach($tables as $i=>$table)
			{
				if(strpos($table,'(')===false)
				{
					if(preg_match('/^(.*?)(?i:\s+as\s+|\s+)(.*)$/',$table,$matches))  // with alias
						$tables[$i]=$this->_connection->quoteTableName($matches[1]).' '.$this->_connection->quoteTableName($matches[2]);
					else
						$tables[$i]=$this->_connection->quoteTableName($table);
				}
			}
			$this->_query['from']=implode(', ',$tables);
		}
		return $this;
	}

	/**
	 * Возвращает часть "FROM" запроса
	 * @return string часть "FROM" (без ключевого слова 'FROM') запроса
	 * @since 1.1.6
	 */
	public function getFrom()
	{
		return isset($this->_query['from']) ? $this->_query['from'] : '';
	}

	/**
	 * Устанавливает часть "FROM" запроса.
	 * @param mixed $value таблицы, из которых производится выборка. За
	 * деталями спецификации данного параметра обратитесь к описанию метода
	 * {@link from()}
	 * @since 1.1.6
	 */
	public function setFrom($value)
	{
		$this->from($value);
	}

	/**
	 * Устанавливает часть "WHERE" запроса.
	 *
	 * Метод требует параметр $conditions и, опционально, параметр $params,
	 * определяющий значения, передаваемые в запрос.
	 *
	 * Параметр $conditions должен быть либо строкой (например, 'id=1') либо
	 * массивом формата <code>array(operator, operand1, operand2, ...)</code>,
	 * где оператор может быть одним из следующих, а возможные операнды зависят
	 * от соответствующего оператора:
	 * <ul>
	 * <li><code>and</code>: операнды соединятся вместе с использованием
	 * ключевого слова AND. Например, array('and', 'id=1', 'id=2') сгенерирует
	 * строку 'id=1 AND id=2'. Если операнд - это массив, он будет
	 * сконвертирован в строку по тем же правилам. Например,
	 * array('and', 'type=1', array('or', 'id=1', 'id=2')) сгенерирует строку
	 * 'type=1 AND (id=1 OR id=2)'. Данный метод ничего не заключает в кавычки
	 * и не экранирует;</li>
	 * <li><code>or</code>: похож на оператор <code>and</code> за исключением
	 * того, что операнды соединяются с использованием OR;</li>
	 * <li><code>in</code>: первый операнд должен быть столбцом или выражением
	 * БД, а второй операнд - массивом, представляющим диапазон значений, в
	 * который должно попасть значение столбца или выражения БД. Например,
	 * array('in', 'id', array(1,2,3)) сгенерирует строку 'id IN (1,2,3)'.
	 * Метод заключает имя столбца в кавычки и экранирует значения диапазона;</li>
	 * <li><code>not in</code>: похож на оператор <code>in</code> за
	 * исключением того, что в сгенерированном условии "IN" заменяется на
	 * "NOT IN";</li>
	 * <li><code>like</code>: первый операнд должен быть столбцом или
	 * выражением БД, а второй операнд - строкой или массивом, представляющим
	 * значения, с которыми сравнивается значение столбца или выражение БД.
	 * Например, array('like', 'name', '%tester%') сгенерирует строку
	 * "name LIKE '%tester%'". Если значения для сравнения переданы в виде
	 * массива, то будут сгенерированы и соединены ключевым словом AND
	 * несколько предикатов LIKE. Например, array('like', 'name', array('%test%', '%sample%'))
	 * сгенерирует строку "name LIKE '%test%' AND name LIKE '%sample%'". Метод
	 * заключает имя столбца в кавычки и экранирует значения для сравнения;</li>
	 * <li><code>not like</code>: похож на оператор <code>like</code> за
	 * исключением того, что в сгенерированном условии "LIKE" заменяется на
	 * "NOT LIKE";</li>
	 * <li><code>or like</code>: похож на оператор <code>like</code> за
	 * исключением того, что для соединения предикатов "LIKE" используется
	 * ключевое слово "OR";</li>
	 * <li><code>or not like</code>: похож на оператор <code>not like</code> за
	 * исключением того, что для соединения предикатов "NOT LIKE" используется
	 * ключевое слово "OR".</li>
	 * </ul>
	 * @param mixed $conditions условия, вставляемые в часть WHERE запроса
	 * @param array $params параметры (имя => значение), связываемые с запросом
	 * @return CDbCommand объект данной команды
	 * @since 1.1.6
	 */
	public function where($conditions, $params=array())
	{
		$this->_query['where']=$this->processConditions($conditions);
		foreach($params as $name=>$value)
			$this->params[$name]=$value;
		return $this;
	}

	/**
	 * Возвращает часть WHERE запроса
	 * @return string часть WHERE (без ключевого слова 'WHERE') запроса
	 * @since 1.1.6
	 */
	public function getWhere()
	{
		return isset($this->_query['where']) ? $this->_query['where'] : '';
	}

	/**
	 * Устанавливает часть WHERE запроса
	 * @param mixed $value часть WHERE запроса. За
	 * деталями спецификации данного параметра обратитесь к описанию метода
	 * {@link where()}
	 * @since 1.1.6
	 */
	public function setWhere($value)
	{
		$this->where($value);
	}

	/**
	 * Добавляет часть INNER JOIN к запросу
	 * @param string $table имя соединяемой таблицы. Имя таблицы может
	 * содержать префикс схемы (например, 'public.tbl_user') и/или псевдоним
	 * таблицы (например, 'tbl_user u'). Метод автоматически заключает в
	 * кавычки имя таблицы, если она не содержит скобок (т.е., таблица передана
	 * не в виде подзапроса или выражения БД)
	 * @param mixed $conditions условие соединения, которое должно быть в части
	 * ON запроса. За деталями спецификации данного параметра обратитесь к
	 * описанию метода {@link where()}
	 * @param array $params параметры (имя => значение), связываемые с запросом
	 * @return CDbCommand объект данной команды
	 * @since 1.1.6
	 */
	public function join($table, $conditions, $params=array())
	{
		return $this->joinInternal('join', $table, $conditions, $params);
	}

	/**
	 * Возвращает часть JOIN запроса
	 * @return mixed часть JOIN запроса. Может быть массивом, представляющим
	 * несколько соединяемых фрагментов, или строкой, представляющей один
	 * фрагмент соединения. Каждый фрагмент соединения должен содержать
	 * правильный оператор соединения (например, LEFT JOIN)
	 * @since 1.1.6
	 */
	public function getJoin()
	{
		return isset($this->_query['join']) ? $this->_query['join'] : '';
	}

	/**
	 * Устанавливает часть JOIN запроса
	 * @param mixed $value часть JOIN запроса. Может быть массивом,
	 * представляющим несколько соединяемых фрагментов, или строкой,
	 * представляющей один фрагмент соединения. Каждый фрагмент соединения
	 * должен содержать правильный оператор соединения (например,
	 * 'LEFT JOIN tbl_profile ON tbl_user.id=tbl_profile.id')
	 * @since 1.1.6
	 */
	public function setJoin($value)
	{
		$this->_query['join']=$value;
	}

	/**
	 * Добавляет часть LEFT OUTER JOIN к запросу
	 * @param string $table имя соединяемой таблицы. Имя таблицы может
	 * содержать префикс схемы (например, 'public.tbl_user') и/или псевдоним
	 * таблицы (например, 'tbl_user u'). Метод автоматически заключает в
	 * кавычки имя таблицы, если она не содержит скобок (т.е., таблица передана
	 * не в виде подзапроса или выражения БД)
	 * @param mixed $conditions условие соединения, которое должно быть в части
	 * ON запроса. За деталями спецификации данного параметра обратитесь к
	 * описанию метода {@link where()}
	 * @param array $params параметры (имя => значение), связываемые с запросом
	 * @return CDbCommand объект данной команды
	 * @since 1.1.6
	 */
	public function leftJoin($table, $conditions, $params=array())
	{
		return $this->joinInternal('left join', $table, $conditions, $params);
	}

	/**
	 * Добавляет часть RIGHT OUTER JOIN к запросу
	 * @param string $table имя соединяемой таблицы. Имя таблицы может
	 * содержать префикс схемы (например, 'public.tbl_user') и/или псевдоним
	 * таблицы (например, 'tbl_user u'). Метод автоматически заключает в
	 * кавычки имя таблицы, если она не содержит скобок (т.е., таблица передана
	 * не в виде подзапроса или выражения БД)
	 * @param mixed $conditions условие соединения, которое должно быть в части
	 * ON запроса. За деталями спецификации данного параметра обратитесь к
	 * описанию метода {@link where()}
	 * @param array $params параметры (имя => значение), связываемые с запросом
	 * @return CDbCommand объект данной команды
	 * @since 1.1.6
	 */
	public function rightJoin($table, $conditions, $params=array())
	{
		return $this->joinInternal('right join', $table, $conditions, $params);
	}

	/**
	 * Добавляет часть CROSS JOIN к запросу. Примечание: не все СУБД
	 * поддерживают поддерживают выражение CROSS JOIN
	 * @param string $table имя соединяемой таблицы. Имя таблицы может
	 * содержать префикс схемы (например, 'public.tbl_user') и/или псевдоним
	 * таблицы (например, 'tbl_user u'). Метод автоматически заключает в
	 * кавычки имя таблицы, если она не содержит скобок (т.е., таблица передана
	 * не в виде подзапроса или выражения БД)
	 * @return CDbCommand объект данной команды
	 * @since 1.1.6
	 */
	public function crossJoin($table)
	{
		return $this->joinInternal('cross join', $table);
	}

	/**
	 * Добавляет часть NATURAL JOIN к запросу. Примечание: не все СУБД
	 * поддерживают поддерживают выражение NATURAL JOIN
	 * @param string $table имя соединяемой таблицы. Имя таблицы может
	 * содержать префикс схемы (например, 'public.tbl_user') и/или псевдоним
	 * таблицы (например, 'tbl_user u'). Метод автоматически заключает в
	 * кавычки имя таблицы, если она не содержит скобок (т.е., таблица передана
	 * не в виде подзапроса или выражения БД)
	 * @return CDbCommand объект данной команды
	 * @since 1.1.6
	 */
	public function naturalJoin($table)
	{
		return $this->joinInternal('natural join', $table);
	}

	/**
	 * Устанавливает часть GROUP BY запроса
	 * @param mixed $columns столбцы, по которым производится группировка.
	 * Столбцы могут быть определены в виде строки (например, "id, name") либо
	 * в виде массива (например, array('id', 'name')). Метод автоматически
	 * заключает в кавычки имена столбцов, если столбец не содержит скобок
	 * (т.е., столбец не содержит выражение БД)
	 * @return CDbCommand объект данной команды
	 * @since 1.1.6
	 */
	public function group($columns)
	{
		if(is_string($columns) && strpos($columns,'(')!==false)
			$this->_query['group']=$columns;
		else
		{
			if(!is_array($columns))
				$columns=preg_split('/\s*,\s*/',trim($columns),-1,PREG_SPLIT_NO_EMPTY);
			foreach($columns as $i=>$column)
			{
				if(is_object($column))
					$columns[$i]=(string)$column;
				else if(strpos($column,'(')===false)
					$columns[$i]=$this->_connection->quoteColumnName($column);
			}
			$this->_query['group']=implode(', ',$columns);
		}
		return $this;
	}

	/**
	 * Возвращает часть GROUP BY запроса
	 * @return string часть GROUP BY (без ключевого слова 'GROUP BY') запроса
	 * @since 1.1.6
	 */
	public function getGroup()
	{
		return isset($this->_query['group']) ? $this->_query['group'] : '';
	}

	/**
	 * Устанавливает часть GROUP BY запроса
	 * @param mixed $value часть GROUP BY запроса. За деталями спецификации
	 * данного параметра обратитесь к описанию метода {@link group()}
	 * @since 1.1.6
	 */
	public function setGroup($value)
	{
		$this->group($value);
	}

	/**
	 * Устанавливает часть HAVING запроса
	 * @param mixed $conditions условия, вставляемые после ключевого слова
	 * HAVING. За деталями спецификации условий обратитесь к описанию метода
	 * {@link where}
	 * @param array $params параметры (имя => значение), связываемые с запросом
	 * @return CDbCommand объект данной команды
	 * @since 1.1.6
	 */
	public function having($conditions, $params=array())
	{
		$this->_query['having']=$this->processConditions($conditions);
		foreach($params as $name=>$value)
			$this->params[$name]=$value;
		return $this;
	}

	/**
	 * Возвращает часть HAVING запроса
	 * @return string часть HAVING (без ключевого слова 'HAVING') запроса
	 * @since 1.1.6
	 */
	public function getHaving()
	{
		return isset($this->_query['having']) ? $this->_query['having'] : '';
	}

	/**
	 * Устанавливает часть HAVING запроса
	 * @param mixed $value часть HAVING запроса. За деталями спецификации
	 * данного параметра обратитесь к описанию метода {@link having()}
	 * @since 1.1.6
	 */
	public function setHaving($value)
	{
		$this->having($value);
	}

	/**
	 * Устанавливает часть ORDER BY запроса
	 * @param mixed $columns столбцы (и направления), по которым производится
	 * сортировка. Столбцы могут быть определены в виде строки (например,
	 * "id ASC, name DESC") либо в виде массива (например, array('id ASC',
	 * 'name DESC')). Метод автоматически заключает в кавычки имена столбцов,
	 * если столбец не содержит скобок (т.е., столбец не содержит выражение БД)
	 * @return CDbCommand объект данной команды
	 * @since 1.1.6
	 */
	public function order($columns)
	{
		if(is_string($columns) && strpos($columns,'(')!==false)
			$this->_query['order']=$columns;
		else
		{
			if(!is_array($columns))
				$columns=preg_split('/\s*,\s*/',trim($columns),-1,PREG_SPLIT_NO_EMPTY);
			foreach($columns as $i=>$column)
			{
				if(is_object($column))
					$columns[$i]=(string)$column;
				else if(strpos($column,'(')===false)
				{
					if(preg_match('/^(.*?)\s+(asc|desc)$/i',$column,$matches))
						$columns[$i]=$this->_connection->quoteColumnName($matches[1]).' '.strtoupper($matches[2]);
					else
						$columns[$i]=$this->_connection->quoteColumnName($column);
				}
			}
			$this->_query['order']=implode(', ',$columns);
		}
		return $this;
	}

	/**
	 * Возвращает часть ORDER BY запроса
	 * @return string часть ORDER BY (без ключевого слова 'ORDER BY') запроса
	 * @since 1.1.6
	 */
	public function getOrder()
	{
		return isset($this->_query['order']) ? $this->_query['order'] : '';
	}

	/**
	 * Устанавливает часть ORDER BY запроса
	 * @param mixed $value часть ORDER BY запроса. За деталями спецификации
	 * данного параметра обратитесь к описанию метода {@link order()}
	 * @since 1.1.6
	 */
	public function setOrder($value)
	{
		$this->order($value);
	}

	/**
	 * Устанавливает часть LIMIT запроса
	 * @param integer $limit лимит
	 * @param integer $offset смещение
	 * @return CDbCommand объект данной команды
	 * @since 1.1.6
	 */
	public function limit($limit, $offset=null)
	{
		$this->_query['limit']=(int)$limit;
		if($offset!==null)
			$this->offset($offset);
		return $this;
	}

	/**
	 * Возвращает часть LIMIT запроса
	 * @return string часть LIMIT (без ключевого слова 'LIMIT') запроса
	 * @since 1.1.6
	 */
	public function getLimit()
	{
		return isset($this->_query['limit']) ? $this->_query['limit'] : -1;
	}

	/**
	 * Устанавливает часть LIMIT запроса
	 * @param mixed $value часть LIMIT запроса. За деталями спецификации
	 * данного параметра обратитесь к описанию метода {@link limit()}
	 * @since 1.1.6
	 */
	public function setLimit($value)
	{
		$this->limit($value);
	}

	/**
	 * Устанавливает часть OFFSET запроса
	 * @param integer $offset смещение
	 * @return CDbCommand объект данной команды
	 * @since 1.1.6
	 */
	public function offset($offset)
	{
		$this->_query['offset']=(int)$offset;
		return $this;
	}

	/**
	 * Возвращает часть OFFSET запроса
	 * @return string часть OFFSET (без ключевого слова 'OFFSET') запроса
	 * @since 1.1.6
	 */
	public function getOffset()
	{
		return isset($this->_query['offset']) ? $this->_query['offset'] : -1;
	}

	/**
	 * Устанавливает часть OFFSET запроса
	 * @param integer $value часть OFFSET запроса. За деталями спецификации
	 * данного параметра обратитесь к описанию метода {@link offset()}
	 * @since 1.1.6
	 */
	public function setOffset($value)
	{
		$this->offset($value);
	}

	/**
	 * Добавляет SQL-выражение с использованием оператора UNION
	 * @param string $sql SQL-выражение, добавляемое при помощи оператора UNION
	 * @return CDbCommand объект данной команды
	 * @since 1.1.6
	 */
	public function union($sql)
	{
		if(isset($this->_query['union']) && is_string($this->_query['union']))
			$this->_query['union']=array($this->_query['union']);

		$this->_query['union'][]=$sql;

		return $this;
	}

	/**
	 * Возвращает часть UNION запроса
	 * @return mixed часть UNION (без ключевого слова 'UNION') запроса. Может
	 * быть строкой или массивом, представляющим несколько объединяемых частей
	 * @since 1.1.6
	 */
	public function getUnion()
	{
		return isset($this->_query['union']) ? $this->_query['union'] : '';
	}

	/**
	 * Устанавливает часть UNION запроса
	 * @param mixed $value часть UNION запроса. Может быть строкой или массивом,
	 * представляющим несколько объединяемых вместе SQL-выражений
	 * @since 1.1.6
	 */
	public function setUnion($value)
	{
		$this->_query['union']=$value;
	}

	/**
	 * Создает и выполняет SQL-выражение INSERT. Метод экранирует имена
	 * столбцов и привязывает к запросу вставляемые значения
	 * @param string $table таблица, в которую вставляются новые строки
	 * @param array $columns данные столбцов (имя => значение), вставляемые в таблицу
	 * @return integer количество строк, затронутых выполнением выражения
	 * @since 1.1.6
	 */
	public function insert($table, $columns)
	{
		$params=array();
		$names=array();
		$placeholders=array();
		foreach($columns as $name=>$value)
		{
			$names[]=$this->_connection->quoteColumnName($name);
			if($value instanceof CDbExpression)
			{
				$placeholders[] = $value->expression;
				foreach($value->params as $n => $v)
					$params[$n] = $v;
			}
			else
			{
				$placeholders[] = ':' . $name;
				$params[':' . $name] = $value;
			}
		}
		$sql='INSERT INTO ' . $this->_connection->quoteTableName($table)
			. ' (' . implode(', ',$names) . ') VALUES ('
			. implode(', ', $placeholders) . ')';
		return $this->setText($sql)->execute($params);
	}

	/**
	 * Создает и выполняет SQL-выражение UPDATE. Метод экранирует имена
	 * столбцов и привязывает к запросу вставляемые значения
	 * @param string $table обновляемая таблица
	 * @param array $columns данные столбцов (имя => значение) для обновления
	 * @param mixed $conditions условия, вставляемые в часть WHERE выражения.
	 * За деталями спецификации данного параметра обратитесь к описанию метода
	 * {@link where}
	 * @param array $params параметры, связываемые с запросом
	 * @return integer количество строк, затронутых выполнением выражения
	 * @since 1.1.6
	 */
	public function update($table, $columns, $conditions='', $params=array())
	{
		$lines=array();
		foreach($columns as $name=>$value)
		{
			if($value instanceof CDbExpression)
			{
				$lines[]=$this->_connection->quoteColumnName($name) . '=' . $value->expression;
				foreach($value->params as $n => $v)
					$params[$n] = $v;
			}
			else
			{
				$lines[]=$this->_connection->quoteColumnName($name) . '=:' . $name;
				$params[':' . $name]=$value;
			}
		}
		$sql='UPDATE ' . $this->_connection->quoteTableName($table) . ' SET ' . implode(', ', $lines);
		if(($where=$this->processConditions($conditions))!='')
			$sql.=' WHERE '.$where;
		return $this->setText($sql)->execute($params);
	}

	/**
	 * Создает и выполняет SQL-выражение DELETE
	 * @param string $table таблица, из которой удаляются данные
	 * @param mixed $conditions условия, вставляемые в часть WHERE выражения.
	 * За деталями спецификации данного параметра обратитесь к описанию метода
	 * {@link where}
	 * @param array $params параметры, связываемые с запросом
	 * @return integer количество строк, затронутых выполнением выражения
	 * @since 1.1.6
	 */
	public function delete($table, $conditions='', $params=array())
	{
		$sql='DELETE FROM ' . $this->_connection->quoteTableName($table);
		if(($where=$this->processConditions($conditions))!='')
			$sql.=' WHERE '.$where;
		return $this->setText($sql)->execute($params);
	}

	/**
	 * Создает и выполняет SQL-выражение для создания новой таблицы в базе данных.
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
	 * @param array $columns столбцы (имя => определение) новой таблицы
	 * @param string $options дополнительный SQL-фрагмент, который будет добавлен в генерируемое SQL-выражение
	 * @return integer количество строк, затронутых выполнением выражения
	 * @since 1.1.6
	 */
	public function createTable($table, $columns, $options=null)
	{
		return $this->setText($this->getConnection()->getSchema()->createTable($table, $columns, $options))->execute();
	}

	/**
	 * Создает и выполняет SQL-выражение для переименования таблицы базы данных
	 * @param string $table переименовываемая таблица. Имя будет заключено в кавычки
	 * @param string $newName новое имя таблицы. Имя будет заключено в кавычки
	 * @return integer количество строк, затронутых выполнением выражения
	 * @since 1.1.6
	 */
	public function renameTable($table, $newName)
	{
		return $this->setText($this->getConnection()->getSchema()->renameTable($table, $newName))->execute();
	}

	/**
	 * Создает и выполняет SQL-выражение для удаления таблицы из базы данных
	 * @param string $table удаляемая таблица. Имя будет заключено в кавычки
	 * @return integer количество строк, затронутых выполнением выражения
	 * @since 1.1.6
	 */
	public function dropTable($table)
	{
		return $this->setText($this->getConnection()->getSchema()->dropTable($table))->execute();
	}

	/**
	 * Создает и выполняет SQL-выражение для очистки таблицы базы данных
	 * @param string $table очищаемая таблица. Имя будет заключено в кавычки
	 * @return integer количество строк, затронутых выполнением выражения
	 * @since 1.1.6
	 */
	public function truncateTable($table)
	{
		$schema=$this->getConnection()->getSchema();
		$n=$this->setText($schema->truncateTable($table))->execute();
		if(strncasecmp($this->getConnection()->getDriverName(),'sqlite',6)===0)
			$schema->resetSequence($schema->getTable($table));
		return $n;
	}

	/**
	 * Создает и выполняет SQL-выражение для добавления нового столбца в таблицу
	 * @param string $table таблица, в которую добавляется столбец. Имя будет заключено в кавычки
	 * @param string $column имя нового столбца. Имя будет заключено в кавычки
	 * @param string $type тип столбца. Для конвертации абстрактного типа столбца в цизический тип
	 * будет вызван метод {@link getColumnType}. Все нераспознанное как абстрактный тип будет оставлено
	 * в сгенерированном SQL-выражении. Например, тип 'string' будет преобразован в 'varchar(255)', а
	 * 'string not null' станет 'varchar(255) not null'
	 * @return integer количество строк, затронутых выполнением выражения
	 * @since 1.1.6
	 */
	public function addColumn($table, $column, $type)
	{
		return $this->setText($this->getConnection()->getSchema()->addColumn($table, $column, $type))->execute();
	}

	/**
	 * Создает и выполняет SQL-выражение для удаления столбца таблицы
	 * @param string $table таблица, столбец которой будет удален. Имя будет заключено в кавычки
	 * @param string $column имя удаляемого столбца. Имя будет заключено в кавычки
	 * @return integer количество строк, затронутых выполнением выражения
	 * @since 1.1.6
	 */
	public function dropColumn($table, $column)
	{
		return $this->setText($this->getConnection()->getSchema()->dropColumn($table, $column))->execute();
	}

	/**
	 * Создает и выполняет SQL-выражение для переименования столбца таблицы
	 * @param string $table таблица, столбец которой будет переименован. Имя будет заключено в кавычки
	 * @param string $name старое имя столбца. Имя будет заключено в кавычки
	 * @param string $newName новое имя столбца. Имя будет заключено в кавычки
	 * @return integer количество строк, затронутых выполнением выражения
	 * @since 1.1.6
	 */
	public function renameColumn($table, $name, $newName)
	{
		return $this->setText($this->getConnection()->getSchema()->renameColumn($table, $name, $newName))->execute();
	}

	/**
	 * Создает и выполняет SQL-выражение для изменения определения столбца таблицы
	 * @param string $table таблица, столбец которой будет изменен. Имя будет заключено в кавычки
	 * @param string $column имя изменяемого столбца. Имя будет заключено в кавычки
	 * @param string $type новый тип столбца. Для конвертации абстрактного типа столбца в цизический тип
	 * будет вызван метод {@link getColumnType}. Все нераспознанное как абстрактный тип будет оставлено
	 * в сгенерированном SQL-выражении. Например, тип 'string' будет преобразован в 'varchar(255)', а
	 * 'string not null' станет 'varchar(255) not null'
	 * @return integer количество строк, затронутых выполнением выражения
	 * @since 1.1.6
	 */
	public function alterColumn($table, $column, $type)
	{
		return $this->setText($this->getConnection()->getSchema()->alterColumn($table, $column, $type))->execute();
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
	 * @return integer количество строк, затронутых выполнением выражения
	 * @since 1.1.6
	 */
	public function addForeignKey($name, $table, $columns, $refTable, $refColumns, $delete=null, $update=null)
	{
		return $this->setText($this->getConnection()->getSchema()->addForeignKey($name, $table, $columns, $refTable, $refColumns, $delete, $update))->execute();
	}

	/**
	 * Создает и выполняет SQL-выражение для удаления внешнего ключа
	 * @param string $name имя удаляемого внешнего ключа. Имя будет заключено в кавычки
	 * @param string $table таблица, внешний ключ которой будет удален. Имя будет заключено в кавычки
	 * @return integer количество строк, затронутых выполнением выражения
	 * @since 1.1.6
	 */
	public function dropForeignKey($name, $table)
	{
		return $this->setText($this->getConnection()->getSchema()->dropForeignKey($name, $table))->execute();
	}

	/**
	 * Создает и выполняет SQL-выражение для создания нового индекса
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
	 * @return integer количество строк, затронутых выполнением выражения
	 * @since 1.1.6
	 */
	public function createIndex($name, $table, $column, $unique=false)
	{
		return $this->setText($this->getConnection()->getSchema()->createIndex($name, $table, $column, $unique))->execute();
	}

	/**
	 * Создает и выполняет SQL-выражение для удаления индекса
	 * @param string $name имя удаляемого индекса. Имя будет заключено в
	 * кавычки
	 * @param string $table таблица, индекс которой будет удален. Имя будет
	 * заключено в кавычки
	 * @return integer количество строк, затронутых выполнением выражения
	 * @since 1.1.6
	 */
	public function dropIndex($name, $table)
	{
		return $this->setText($this->getConnection()->getSchema()->dropIndex($name, $table))->execute();
	}

	/**
	 * Генерирует строку условия, вставляемую в часть WHERE запроса
	 * @param mixed $conditions условия, вставляемые в часть WHERE запроса
	 * @return string строка условия вставляемая в часть WHERE запроса
	 */
	private function processConditions($conditions)
	{
		if(!is_array($conditions))
			return $conditions;
		else if($conditions===array())
			return '';
		$n=count($conditions);
		$operator=strtoupper($conditions[0]);
		if($operator==='OR' || $operator==='AND')
		{
			$parts=array();
			for($i=1;$i<$n;++$i)
			{
				$condition=$this->processConditions($conditions[$i]);
				if($condition!=='')
					$parts[]='('.$condition.')';
			}
			return $parts===array() ? '' : implode(' '.$operator.' ', $parts);
		}

		if(!isset($conditions[1],$conditions[2]))
			return '';

		$column=$conditions[1];
		if(strpos($column,'(')===false)
			$column=$this->_connection->quoteColumnName($column);

		$values=$conditions[2];
		if(!is_array($values))
			$values=array($values);

		if($operator==='IN' || $operator==='NOT IN')
		{
			if($values===array())
				return $operator==='IN' ? '0=1' : '';
			foreach($values as $i=>$value)
			{
				if(is_string($value))
					$values[$i]=$this->_connection->quoteValue($value);
				else
					$values[$i]=(string)$value;
			}
			return $column.' '.$operator.' ('.implode(', ',$values).')';
		}

		if($operator==='LIKE' || $operator==='NOT LIKE' || $operator==='OR LIKE' || $operator==='OR NOT LIKE')
		{
			if($values===array())
				return $operator==='LIKE' || $operator==='OR LIKE' ? '0=1' : '';

			if($operator==='LIKE' || $operator==='NOT LIKE')
				$andor=' AND ';
			else
			{
				$andor=' OR ';
				$operator=$operator==='OR LIKE' ? 'LIKE' : 'NOT LIKE';
			}
			$expressions=array();
			foreach($values as $value)
				$expressions[]=$column.' '.$operator.' '.$this->_connection->quoteValue($value);
			return implode($andor,$expressions);
		}

		throw new CDbException(Yii::t('yii', 'Unknown operator "{operator}".', array('{operator}'=>$operator)));
	}

	/**
	 * Добавляет часть JOIN к запросу
	 * @param string $type тип соединения ('join', 'left join', 'right join', 'cross join', 'natural join')
	 * @param string $table соединяемая таблица. Имя таблицы может содержать
	 * префикс схемы (например, 'public.tbl_user') и/или псевдоним
	 * таблицы (например, 'tbl_user u'). Метод автоматически заключает в
	 * кавычки имя таблицы, если она не содержит скобок (т.е., таблица передана
	 * не в виде подзапроса или выражения БД)
	 * @param mixed $conditions условие соединения, которое должно быть в части
	 * ON запроса. За деталями спецификации данного параметра обратитесь к
	 * описанию метода {@link where()}
	 * @param array $params параметры (имя => значение), связываемые с запросом
	 * @return CDbCommand объект данной команды
	 * @since 1.1.6
	 */
	private function joinInternal($type, $table, $conditions='', $params=array())
	{
		if(strpos($table,'(')===false)
		{
			if(preg_match('/^(.*?)(?i:\s+as\s+|\s+)(.*)$/',$table,$matches))  // with alias
				$table=$this->_connection->quoteTableName($matches[1]).' '.$this->_connection->quoteTableName($matches[2]);
			else
				$table=$this->_connection->quoteTableName($table);
		}

		$conditions=$this->processConditions($conditions);
		if($conditions!='')
			$conditions=' ON '.$conditions;

		if(isset($this->_query['join']) && is_string($this->_query['join']))
			$this->_query['join']=array($this->_query['join']);

		$this->_query['join'][]=strtoupper($type) . ' ' . $table . $conditions;

		foreach($params as $name=>$value)
			$this->params[$name]=$value;
		return $this;
	}
}
