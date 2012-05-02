<?php
/**
 * Файл класса CDbConnection
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Класс CDbConnection представляет подключение БД.
 *
 * CDbConnection работает вместе с классами {@link CDbCommand}, {@link CDbDataReader}
 * и {@link CDbTransaction} для предоставления доступа к данным в разных СУБД с
 * использованием общего набора API. Это обертка для расширения PHP {@link http://www.php.net/manual/en/ref.pdo.php PDO}.
 *
 * Для установки соединения присвойте свойству {@link setActive active} значение true после
 * определения свойств {@link connectionString}, {@link username} и {@link password}.
 *
 * Следующий пример показывает, как создавать экземпляр соединения CDbConnection и устанавливать
 * реальное соединения с базой данных:
 * <pre>
 * $connection=new CDbConnection($dsn,$username,$password);
 * $connection->active=true;
 * </pre>
 *
 * После установки соединения БД можно выполнять SQL-выражения следующим образом:
 * <pre>
 * $command=$connection->createCommand($sqlStatement);
 * $command->execute();   // выполнение SQL-выражение без возврата данных (не запрос)
 * // выполнение SQL-запроса и получение результирующего набора
 * $reader=$command->query();
 *
 * // $row - это массив, представляющий строку данных
 * foreach($reader as $row) ...
 * </pre>
 *
 * Можно подготавливать SQL-выражения и связывать параметры с подготовленным SQL-выражением:
 * <pre>
 * $command=$connection->createCommand($sqlStatement);
 * $command->bindParam($name1,$value1);
 * $command->bindParam($name2,$value2);
 * $command->execute();
 * </pre>
 *
 * Для использования транзакций используется код, аналогичный следующему:
 * <pre>
 * $transaction=$connection->beginTransaction();
 * try
 * {
 *    $connection->createCommand($sql1)->execute();
 *    $connection->createCommand($sql2)->execute();
 *    //.... выполнение других SQL-выражений
 *    $transaction->commit();
 * }
 * catch(Exception $e)
 * {
 *    $transaction->rollBack();
 * }
 * </pre>
 *
 * Класс CDbConnection также предоставляет набор методов для поддержки установки и запроса
 * некоторых атрибутов СУБД, таких как {@link getNullConversion nullConversion}.
 *
 * Т.к. CDbConnection реализует интерфейс IApplicationComponent, то его можно использовать
 * в качестве компонента приложения и настроить в конфигурации приложения как показано ниже:
 * <pre>
 * array(
 *     'components'=>array(
 *         'db'=>array(
 *             'class'=>'CDbConnection',
 *             'connectionString'=>'sqlite:path/to/dbfile',
 *         ),
 *     ),
 * )
 * </pre>
 *
 * @property boolean $active установлено ли соединение БД
 * @property PDO $pdoInstance экземпляр PDO-класса; null, если соединение еще
 * не установлено
 * @property CDbTransaction $currentTransaction текущая активная транзакция;
 * null, если активных транзакций нет
 * @property CDbSchema $schema схема БД для данного соединения
 * @property CDbCommandBuilder $commandBuilder построитель команд для данного
 * соединения
 * @property string $lastInsertID идентификатор последней вставленной строки
 * или последнего значения, полученного из объекта последовательности
 * @property mixed $columnCase регистр имен столбцов
 * @property mixed $nullConversion значение того, как конвертируются значения
 * null и пустые строки
 * @property boolean $autoCommit будут ли запросы на обновление и добавление
 * записей БД автоматически подтверждаться
 * @property boolean $persistent является ли соединение постоянным или нет
 * @property string $driverName имя драйвера БД
 * @property string $clientVersion информация о версии драйвера БД
 * @property string $connectionStatus статус соединения
 * @property boolean $prefetch выполняет ли соединение предварительную выборку
 * (prefetching) данных
 * @property string $serverInfo информация о сервере СУБД
 * @property string $serverVersion информация о версии сервера СУБД
 * @property integer $timeout настройки времени ожидания соединения
 * @property array $attributes атрибуты (в виде имя => значение), явно
 * установленные ранее для соединения БД
 * @property array $stats массив, первый элемент которого показывает количество
 * выполненных SQL-выражений, а второй - общее затраченное на выполнение
 * SQL-выражений время
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CDbConnection.php 3515 2011-12-28 12:29:24Z mdomba $
 * @package system.db
 * @since 1.0
 */
class CDbConnection extends CApplicationComponent
{
	/**
	 * @var string источник данных (Data Source Name, DSN), содержит информацию, требуемую для подключения к БД.
	 * @see http://www.php.net/manual/en/function.PDO-construct.php
	 *
	 * Примечание: если вы используете GBK или BIG5, то настоятельно рекомендуется обновить PHP
	 * до версии, большей 5.3.6 и определять кодировку через DSN -
	 * 'mysql:dbname=mydatabase;host=127.0.0.1;charset=GBK;'
	 */
	public $connectionString;
	/**
	 * @var string имя пользователя для установки соединения БД. По умолчанию - пустая строка
	 */
	public $username='';
	/**
	 * @var string пароль для установки соединения БД. По умолчанию - пустая строка
	 */
	public $password='';
	/**
	 * @var integer количество секунд, в течение которыхметаданные таблиц могут оставаться в кэше валидными.
	 * Для индикации того, что кэширование схемы не используется, устанавливается значение 0 или отрицательное.
	 * Если значение больше нуля и первичный кэш включен, то метаданные таблиц будут кэшироваться
	 * @see schemaCachingExclude
	 */
	public $schemaCachingDuration=0;
	/**
	 * @var array список таблиц, метаданные которых НЕ должны кэшироваться. По умолчанию - пустой массив
	 * @see schemaCachingDuration
	 */
	public $schemaCachingExclude=array();
	/**
	 * @var string идентификатор компонента приложения кэша, используемого для кэширования метаданных таблиц.
	 * По умолчанию - 'cache' - основной компонент приложения кэша.
	 * Для отключения кэширования метаданных таблиц необходимо установить данное свойство в значение false
	 */
	public $schemaCacheID='cache';
	/**
	 * @var integer количество секунд, в течение которых результат запроса в кэше остается валидным.
	 * Для отключения кэширования необходимо установить значение, равным нулю или меньше нуля (поведение по умолчанию).
	 *
	 * Для включения кэширования данное свойство должно быть целым положительным числом и
	 * свойство {@link queryCacheID} должно ссылаться на действительный компонент кэширования.
	 *
	 * Метод {@link cache()} предоставлен как простой способ установки данного свойства и
	 * и свойства {@link queryCachingDependency} на лету
	 *
	 * @see cache
	 * @see queryCachingDependency
	 * @see queryCacheID
	 * @since 1.1.7
	 */
	public $queryCachingDuration=0;
	/**
	 * @var CCacheDependency зависимость, используемая при сохранении результата запроса в кэш
	 * @see queryCachingDuration
	 * @since 1.1.7
	 */
	public $queryCachingDependency;
	/**
	 * @var integer количество SQL-выражений, которые должны быть кэшированы
	 * в дальнейшем. Если значение равно 0, то даже если кэширование запросов
	 * включено, запросы не будут кэшированы. Примечание: каждый раз после
	 * выполнения SQL-выражения (выполняемом на сервере БД или запрошенном из
	 * кэша запросов), данное свойство будет уменьшаться на единицу до нуля
	 * @since 1.1.7
	 */
	public $queryCachingCount=0;
	/**
	 * @var string идентификатор компонента приложения кэша, используемый для
	 * кэширования запросов. По умолчанию - 'cache' - основной компонент
	 * приложения кэша. Для отключения кэширования запросов необходимо
	 * установить данное свойство в значение false
	 * @since 1.1.7
	 */
	public $queryCacheID='cache';
	/**
	 * @var boolean должно ли автоматически утанавливаться соединение при инициализации
	 * компонента. По умолчанию - true. Примечание: свойство имеет значение только в случае,
	 * когда объект класса CDbConnection используется в качестве компонента приложения
	 */
	public $autoConnect=true;
	/**
	 * @var string кодировка, используемая для соединения БД. Свойство
	 * используется только для СУБД MySQL и PostgreSQL. По умолчанию - null,
	 * т.е. используется кодировка, определенная в БД по умолчанию.
	 *
	 * Примечание: если используется GBK или BIG5, то настоятельно
	 * рекомендуется обновить PHP до версии 5.3.6+ и установить кодировку через
	 * DSN, например, так - 'mysql:dbname=mydatabase;host=127.0.0.1;charset=GBK;'
	 */
	public $charset;
	/**
	 * @var boolean включена ли эмуляция подготовки запроса. По умолчанию -
	 * false, т.е. PDO будет использовать встроенную поддержку подготовки
	 * запроса, если это возможно. Для некоторых СУБД (например, MySQL), может
	 * понадобиться установить свойство в значение true, чтобы PDO мог
	 * эмулировать поддержку подготовки запроса для обхода нестабильной
	 * встроенной поддержки подготовки запроса. Примечание: свойство имеет
	 * значение только для PHP версий 5.1.3 или выше. По умолчанию - null, т.е.
	 * значение параметра ATTR_EMULATE_PREPARES расширения PDO не изменяется
	 */
	public $emulatePrepare;
	/**
	 * @var boolean журналировать ли значения, связываемые с подготовленным
	 * SQL-выражением. По умолчанию - false. Во время разработки можно
	 * установить значение true, чтобы журналировать значения параметров в
	 * целях отладки. Нужно быть внимательным при установке данного свойства,
	 * т.к. журналирование значений параметров может быть дорогим и оказывать
	 * значительное влияние на производительность приложения
	 */
	public $enableParamLogging=false;
	/**
	 * @var boolean включено ли профилирование выполняемых SQL-выражений. По
	 * умолчанию - false. В основном, профилирование включается во время
	 * разработки для обнаружения узких мест в выполнении SQL-выражений
	 */
	public $enableProfiling=false;
	/**
	 * @var string префикс по умолчанию для имен таблиц. По умолчанию - null,
	 * т.е. префикс не используется. При установке данного свойства, такая
	 * метка как '{{tableName}}' в свойстве {@link CDbCommand::text} будет
	 * заменена на строку 'prefixTableName', где 'prefix' - значение данного
	 * свойства
	 * @since 1.1.0
	 */
	public $tablePrefix;
	/**
	 * @var array список SQL-выражений, которые должны выполняться сразу после
	 * установки соединения БД
	 * @since 1.1.1
	 */
	public $initSQLs;
	/**
	 * @var array массив соответствий между PDO-драйверами и именами классов
	 * схемы. Класс схемы БД может быть определен с использованием псевдонимов
	 * путей
	 * @since 1.1.6
	 */
	public $driverMap=array(
		'pgsql'=>'CPgsqlSchema',    // PostgreSQL
		'mysqli'=>'CMysqlSchema',   // MySQL
		'mysql'=>'CMysqlSchema',    // MySQL
		'sqlite'=>'CSqliteSchema',  // sqlite 3
		'sqlite2'=>'CSqliteSchema', // sqlite 2
		'mssql'=>'CMssqlSchema',    // Mssql driver on windows hosts
		'dblib'=>'CMssqlSchema',    // dblib drivers on linux (and maybe others os) hosts
		'sqlsrv'=>'CMssqlSchema',   // Mssql
		'oci'=>'COciSchema',        // Oracle driver
	);

	/**
	 * @var string специальный класс-обертка PDO
	 * @since 1.1.8
	 */
	public $pdoClass = 'PDO';

	private $_attributes=array();
	private $_active=false;
	private $_pdo;
	private $_transaction;
	private $_schema;


	/**
	 * Конструктор.
	 * Примечание: соединение БД не устанавливается при создании экземпляра
	 * соединения. Для установки соединения задайте свойству
	 * {@link setActive active} значение true
	 * @param string $dsn источник данных (The Data Source Name, DSN), содержит
	 * информацию, требуемую для подключения к базе данных
	 * @param string $username имя пользователя для строки DSN
	 * @param string $password пароль для строки DSN
	 * @see http://www.php.net/manual/en/function.PDO-construct.php
	 */
	public function __construct($dsn='',$username='',$password='')
	{
		$this->connectionString=$dsn;
		$this->username=$username;
		$this->password=$password;
	}

	/**
	 * Закрывать соединение при сериализации
	 * @return array
	 */
	public function __sleep()
	{
		$this->close();
		return array_keys(get_object_vars($this));
	}

	/**
	 * Возвращает список доступных PDO-драйверов
	 * @return array список доступных PDO-драйверов
	 * @see http://www.php.net/manual/en/function.PDO-getAvailableDrivers.php
	 */
	public static function getAvailableDrivers()
	{
		return PDO::getAvailableDrivers();
	}

	/**
	 * Инициализирует компонент. Данный метод требуется интерфейсом
	 * {@link IApplicationComponent} и вызывается приложением при использовании
	 * CDbConnection в качестве компонента приложения. При переопределении
	 * данного метода убедитесь, что вызывается родительская реализация, чтобы
	 * компонент может быть отмечен как инициализированный
	 */
	public function init()
	{
		parent::init();
		if($this->autoConnect)
			$this->setActive(true);
	}

	/**
	 * Установлено ли соединение БД
	 * @return boolean установлено ли соединение БД
	 */
	public function getActive()
	{
		return $this->_active;
	}

	/**
	 * Открывает или закрывает соединение БД
	 * @param boolean $value открыто ли соединение БД
	 * @throws CException вызывается, если попытка открытия соединение
	 * завершилась с ошибкой
	 */
	public function setActive($value)
	{
		if($value!=$this->_active)
		{
			if($value)
				$this->open();
			else
				$this->close();
		}
	}

	/**
	 * Устанавливает параметры кэширования запросов. Метод может быть
	 * использован для включения и отключения кэширования запросов. Установка
	 * параметра $duration в значение 0 отключает кэширование запросов. В ином
	 * случае результат запроса нового SQL-выражения, выполняемого далее, будет
	 * сохранен в кэше и будет оставаться валидным в течение установленного
	 * срока. При повторном выполнении запроса результат запроса может быть
	 * получен непосредственно из кэша без реального выполнения SQL-выражения
	 * @param integer $duration время в секундах, в течение которого результаты
	 * запросов в кэше могут оставаться валидными. Если установлено в значение
	 * 0, то кэширование запросов отключено
	 * @param CCacheDependency $dependency зависимость, используемая при
	 * сохранении результатов запросов в кэш
	 * @param integer $queryCount количество SQL-запросов для кэширования после
	 * выполнения данного метода. По умолчанию - 1, т.е. следующий запрос будет
	 * кэширован
	 * @return CDbConnection экземпляр соединения БД
	 * @since 1.1.7
	 */
	public function cache($duration, $dependency=null, $queryCount=1)
	{
		$this->queryCachingDuration=$duration;
		$this->queryCachingDependency=$dependency;
		$this->queryCachingCount=$queryCount;
		return $this;
	}

	/**
	 * Открывает соединение БД, если оно еще не открыто
	 * @throws CException вызывается, если попытка открытия соединение
	 * завершилась с ошибкой
	 */
	protected function open()
	{
		if($this->_pdo===null)
		{
			if(empty($this->connectionString))
				throw new CDbException(Yii::t('yii','CDbConnection.connectionString cannot be empty.'));
			try
			{
				Yii::trace('Opening DB connection','system.db.CDbConnection');
				$this->_pdo=$this->createPdoInstance();
				$this->initConnection($this->_pdo);
				$this->_active=true;
			}
			catch(PDOException $e)
			{
				if(YII_DEBUG)
				{
					throw new CDbException(Yii::t('yii','CDbConnection failed to open the DB connection: {error}',
						array('{error}'=>$e->getMessage())),(int)$e->getCode(),$e->errorInfo);
				}
				else
				{
					Yii::log($e->getMessage(),CLogger::LEVEL_ERROR,'exception.CDbException');
					throw new CDbException(Yii::t('yii','CDbConnection failed to open the DB connection.'),(int)$e->getCode(),$e->errorInfo);
				}
			}
		}
	}

	/**
	 * Закрывает текущее активное соединение БД. Ничего не делает, если
	 * соединение уже закрыто
	 */
	protected function close()
	{
		Yii::trace('Closing DB connection','system.db.CDbConnection');
		$this->_pdo=null;
		$this->_active=false;
		$this->_schema=null;
	}

	/**
	 * Создает экземпляр PDO-класса. Если в PDO-драйвере нет некоторого
	 * функционала, можно использовать класс-адаптер для предоставления этого
	 * функционала
	 * @return PDO экземпляр PDO-класса
	 */
	protected function createPdoInstance()
	{
		$pdoClass=$this->pdoClass;
		if(($pos=strpos($this->connectionString,':'))!==false)
		{
			$driver=strtolower(substr($this->connectionString,0,$pos));
			if($driver==='mssql' || $driver==='dblib' || $driver==='sqlsrv')
				$pdoClass='CMssqlPdoAdapter';
		}
		return new $pdoClass($this->connectionString,$this->username,
									$this->password,$this->_attributes);
	}

	/**
	 * Инициализирует открытое соединение БД. Метод вызывает сразу после
	 * установки соединения. Реализация по умолчанию устанавливает кодировку
	 * для соединений баз MySQL и PostgreSQL
	 * @param PDO $pdo экземпляр PDO-класса
	 */
	protected function initConnection($pdo)
	{
		$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		if($this->emulatePrepare!==null && constant('PDO::ATTR_EMULATE_PREPARES'))
			$pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES,$this->emulatePrepare);
		if($this->charset!==null)
		{
			$driver=strtolower($pdo->getAttribute(PDO::ATTR_DRIVER_NAME));
			if(in_array($driver,array('pgsql','mysql','mysqli')))
				$pdo->exec('SET NAMES '.$pdo->quote($this->charset));
		}
		if($this->initSQLs!==null)
		{
			foreach($this->initSQLs as $sql)
				$pdo->exec($sql);
		}
	}

	/**
	 * Возвращает экземпляр PDO-класса
	 * @return PDO экземпляр PDO-класса; null, если соединение еще не
	 * установлено
	 */
	public function getPdoInstance()
	{
		return $this->_pdo;
	}

	/**
	 * Создает команду для выполнения
	 * @param mixed $query выполняемый запрос. Может быть строкой,
	 * представляющей SQL-выражение, или массивом, представляющим разные части
	 * SQL-выражения. За подробностями о передаче массива в качестве параметра
	 * обратитесь к описанию метода {@link CDbCommand::__construct}. Если
	 * данный параметр не передан, необходимо будет вызвать методы создания
	 * запросов класса {@link CDbCommand}
	 * @return CDbCommand команда БД
	 */
	public function createCommand($query=null)
	{
		$this->setActive(true);
		return new CDbCommand($this,$query);
	}

	/**
	 * Возвращает текущую активную транзакцию
	 * @return CDbTransaction текущая активная транзакция; null, если активных
	 * транзакций нет
	 */
	public function getCurrentTransaction()
	{
		if($this->_transaction!==null)
		{
			if($this->_transaction->getActive())
				return $this->_transaction;
		}
		return null;
	}

	/**
	 * Начинает транзакцию
	 * @return CDbTransaction инициированная транзакция
	 */
	public function beginTransaction()
	{
		Yii::trace('Starting transaction','system.db.CDbConnection');
		$this->setActive(true);
		$this->_pdo->beginTransaction();
		return $this->_transaction=new CDbTransaction($this);
	}

	/**
	 * Возвращает схему БД для данного соединения
	 * @return CDbSchema схема БД для данного соединения
	 */
	public function getSchema()
	{
		if($this->_schema!==null)
			return $this->_schema;
		else
		{
			$driver=$this->getDriverName();
			if(isset($this->driverMap[$driver]))
				return $this->_schema=Yii::createComponent($this->driverMap[$driver], $this);
			else
				throw new CDbException(Yii::t('yii','CDbConnection does not support reading schema for {driver} database.',
					array('{driver}'=>$driver)));
		}
	}

	/**
	 * Возвращает построитель команд для данного соединения
	 * @return CDbCommandBuilder построитель команд для данного соединения
	 */
	public function getCommandBuilder()
	{
		return $this->getSchema()->getCommandBuilder();
	}

	/**
	 * Возвращает идентификатор последней вставленной строки или значение
	 * последовательности
	 * @param string $sequenceName имя объекта последовательности (требуется
	 * некоторыми СУБД)
	 * @return string идентификатор последней вставленной строки или последнего
	 * значения, полученного из объекта последовательности
	 * @see http://www.php.net/manual/en/function.PDO-lastInsertId.php
	 */
	public function getLastInsertID($sequenceName='')
	{
		$this->setActive(true);
		return $this->_pdo->lastInsertId($sequenceName);
	}

	/**
	 * Заключает в кавычки значение строки для использования в запросе
	 * @param string $str заключаемая в кавычки строка
	 * @return string заключённая в кавычки строка
	 * @see http://www.php.net/manual/en/function.PDO-quote.php
	 */
	public function quoteValue($str)
	{
		if(is_int($str) || is_float($str))
			return $str;

		$this->setActive(true);
		if(($value=$this->_pdo->quote($str))!==false)
			return $value;
		else  // the driver doesn't support quote (e.g. oci)
			return "'" . addcslashes(str_replace("'", "''", $str), "\000\n\r\\\032") . "'";
	}

	/**
	 * Заключает в кавычки имя таблицы для использования в запросе. Если имя
	 * таблицы содержит префикс схемы, то данный префикс также будет заключен в кавычки
	 * @param string $name имя таблицы
	 * @return string заключенное в кавычки имя таблицы
	 */
	public function quoteTableName($name)
	{
		return $this->getSchema()->quoteTableName($name);
	}

	/**
	 * Заключает в кавычки имя столбца для использования в запросе. Если имя
	 * столбца содержит префикс , то данный префикс также будет заключен в кавычки
	 * @param string $name имя столбца
	 * @return string заключенное в кавычки имя столбца
	 */
	public function quoteColumnName($name)
	{
		return $this->getSchema()->quoteColumnName($name);
	}

	/**
	 * Определяет тип PDO по переданному типу PHP
	 * @param string $type тип PHP (полученный вызывом функции gettype())
	 * @return integer соответствующий тип PDO
	 */
	public function getPdoType($type)
	{
		static $map=array
		(
			'boolean'=>PDO::PARAM_BOOL,
			'integer'=>PDO::PARAM_INT,
			'string'=>PDO::PARAM_STR,
			'NULL'=>PDO::PARAM_NULL,
		);
		return isset($map[$type]) ? $map[$type] : PDO::PARAM_STR;
	}

	/**
	 * Возвращает регистр имен столбцов
	 * @return mixed регистр имен столбцов
	 * @see http://www.php.net/manual/en/pdo.setattribute.php
	 */
	public function getColumnCase()
	{
		return $this->getAttribute(PDO::ATTR_CASE);
	}

	/**
	 * Устанавливает регистр имен столбцов
	 * @param mixed $value регистр имен столбцов
	 * @see http://www.php.net/manual/en/pdo.setattribute.php
	 */
	public function setColumnCase($value)
	{
		$this->setAttribute(PDO::ATTR_CASE,$value);
	}

	/**
	 * Возвращает значение того, как конвертируются значения null и пустые строки
	 * @return mixed значение того, как конвертируются значения null и пустые строки
	 * @see http://www.php.net/manual/en/pdo.setattribute.php
	 */
	public function getNullConversion()
	{
		return $this->getAttribute(PDO::ATTR_ORACLE_NULLS);
	}

	/**
	 * Устанавливает значение того, как конвертируются значения null и пустые строки
	 * @param mixed $value значение того, как конвертируются значения null и
	 * пустые строки
	 * @see http://www.php.net/manual/en/pdo.setattribute.php
	 */
	public function setNullConversion($value)
	{
		$this->setAttribute(PDO::ATTR_ORACLE_NULLS,$value);
	}

	/**
	 * Возвращает флаг, показывающий, будут ли запросы на обновление и
	 * добавление записей БД автоматически подтверждаться. Некоторые СУБД
	 * (например, sqlite) могут не поддерживать данную функцию
	 * @return boolean будут ли запросы на обновление и добавление записей БД
	 * автоматически подтверждаться
	 */
	public function getAutoCommit()
	{
		return $this->getAttribute(PDO::ATTR_AUTOCOMMIT);
	}

	/**
	 * Устанавливает флаг, показывающий, будут ли запросы на обновление и
	 * добавление записей БД автоматически подтверждаться. Некоторые СУБД
	 * (например, sqlite) могут не поддерживать данную функцию
	 * @param boolean $value будут ли запросы на обновление и добавление
	 * записей БД автоматически подтверждаться
	 */
	public function setAutoCommit($value)
	{
		$this->setAttribute(PDO::ATTR_AUTOCOMMIT,$value);
	}

	/**
	 * Возвращает флаг, показывающий, является ли соединение постоянным или
	 * нет. Некоторые СУБД (например, sqlite) могут не поддерживать данную
	 * функцию
	 * @return boolean является ли соединение постоянным или нет
	 */
	public function getPersistent()
	{
		return $this->getAttribute(PDO::ATTR_PERSISTENT);
	}

	/**
	 * Устанавливает флаг, показывающий, является ли соединение постоянным или
	 * нет. Некоторые СУБД (например, sqlite) могут не поддерживать данную
	 * функцию
	 * @param boolean $value является ли соединение постоянным или нет
	 */
	public function setPersistent($value)
	{
		return $this->setAttribute(PDO::ATTR_PERSISTENT,$value);
	}

	/**
	 * Возвращает имя драйвера БД
	 * @return string имя драйвера БД
	 */
	public function getDriverName()
	{
		if(($pos=strpos($this->connectionString, ':'))!==false)
			return strtolower(substr($this->connectionString, 0, $pos));
		// return $this->getAttribute(PDO::ATTR_DRIVER_NAME);
	}

	/**
	 * Возвращает информацию о версии драйвера БД
	 * @return string информация о версии драйвера БД
	 */
	public function getClientVersion()
	{
		return $this->getAttribute(PDO::ATTR_CLIENT_VERSION);
	}

	/**
	 * Возвращает статус соединения. Некоторые СУБД (например, sqlite) могут не
	 * поддерживать данную функцию
	 * @return string статус соединения
	 */
	public function getConnectionStatus()
	{
		return $this->getAttribute(PDO::ATTR_CONNECTION_STATUS);
	}

	/**
	 * Возвращает флаг, показывающий, выполняет ли соединение предварительную
	 * выборку (prefetching) данных
	 * @return boolean выполняет ли соединение предварительную выборку
	 * (prefetching) данных
	 */
	public function getPrefetch()
	{
		return $this->getAttribute(PDO::ATTR_PREFETCH);
	}

	/**
	 * Возвращает информацию о сервере СУБД
	 * @return string информация о сервере СУБД
	 */
	public function getServerInfo()
	{
		return $this->getAttribute(PDO::ATTR_SERVER_INFO);
	}

	/**
	 * Возвращает информацию о версии сервера СУБД
	 * @return string информация о версии сервера СУБД
	 */
	public function getServerVersion()
	{
		return $this->getAttribute(PDO::ATTR_SERVER_VERSION);
	}

	/**
	 * Возвращает настройки времени ожидания соединения
	 * @return integer настройки времени ожидания соединения
	 */
	public function getTimeout()
	{
		return $this->getAttribute(PDO::ATTR_TIMEOUT);
	}

	/**
	 * Получает информацию о определенном атрибуте соединения БД
	 * @param integer $name запрашиваемый атрибут
	 * @return mixed соответствующая информация атрибута
	 * @see http://www.php.net/manual/en/function.PDO-getAttribute.php
	 */
	public function getAttribute($name)
	{
		$this->setActive(true);
		return $this->_pdo->getAttribute($name);
	}

	/**
	 * Устанавливает атрибут соединения БД
	 * @param integer $name устанавливаемый атрибут
	 * @param mixed $value значение атрибута
	 * @see http://www.php.net/manual/en/function.PDO-setAttribute.php
	 */
	public function setAttribute($name,$value)
	{
		if($this->_pdo instanceof PDO)
			$this->_pdo->setAttribute($name,$value);
		else
			$this->_attributes[$name]=$value;
	}

	/**
	 * Возвращает атрибуты, явно установленные ранее для соединения БД
	 * @return array атрибуты (в виед имя => значение), явно установленные
	 * ранее для соединения БД
	 * @see setAttributes
	 * @since 1.1.7
	 */
	public function getAttributes()
	{
		return $this->_attributes;
	}

	/**
	 * Устанавливает набор атрибутов для соединения БД
	 * @param array $values устанавливаемые атрибуты (имя => значение)
	 * @see setAttribute
	 * @since 1.1.7
	 */
	public function setAttributes($values)
	{
		foreach($values as $name=>$value)
			$this->_attributes[$name]=$value;
	}

	/**
	 * Возвращает статистическую информацию о выполнении SQL-выражений.
	 * Возвращаемые результаты включают количество выполненных SQL-выражений и
	 * общее затраченное на выполнение время. Для использования данного метода,
	 * необходимо установить параметр {@link enableProfiling} в значение true
	 * @return array массив, первый элемент которого показывает количество
	 * выполненных SQL-выражений, а второй - общее затраченное на выполнение
	 * SQL-выражений время
	 */
	public function getStats()
	{
		$logger=Yii::getLogger();
		$timings=$logger->getProfilingResults(null,'system.db.CDbCommand.query');
		$count=count($timings);
		$time=array_sum($timings);
		$timings=$logger->getProfilingResults(null,'system.db.CDbCommand.execute');
		$count+=count($timings);
		$time+=array_sum($timings);
		return array($count,$time);
	}
}
