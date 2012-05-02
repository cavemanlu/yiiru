<?php
/**
 * Файл класса CMemCache
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Класс CMemCache реализует кэш-компонент приложения, основанный на {@link http://memcached.org/ memcached}.
 *
 * Клмпонент CMemCache может быть сконфигурирован списком серверов memcache, установив
 * его свойство {@link setServers servers}. По умолчанию CMemCache предполагает, что
 * сервер memcache работает на хосте localhost на порту 11211.
 *
 * Обратитесь к документации {@link CCache} за информацией об обычных операциях кэша, поддерживаемых компонентом CMemCache.
 *
 * Примечание: обеспечения защиты данных в memcache нет.
 * Все данные в memcache могут быть доступны для любого процесса системы.
 *
 * Для использования CMemCache в качестве компонента приложения, сконфигурируйте приложение следующим образом:
 * <pre>
 * array(
 *     'components'=>array(
 *         'cache'=>array(
 *             'class'=>'CMemCache',
 *             'servers'=>array(
 *                 array(
 *                     'host'=>'server1',
 *                     'port'=>11211,
 *                     'weight'=>60,
 *                 ),
 *                 array(
 *                     'host'=>'server2',
 *                     'port'=>11211,
 *                     'weight'=>40,
 *                 ),
 *             ),
 *         ),
 *     ),
 * )
 * </pre>
 * В коде выше используется два сервера memcache - server1 и server2.
 * Вы можете настроить больше свойств каждого сервера, включая:
 * host, port, persistent, weight, timeout, retryInterval, status.
 * Обратитесь за деталями к {@link http://www.php.net/manual/en/function.memcache-addserver.php}.
 *
 * CMemCache может использоваться с {@link http://pecl.php.net/package/memcached memcached}.
 * Для этого установите свойство {@link useMemcached} в значение true.
 *
 * @property mixed $memCache экземпляр memcache (или memcached, если свойство
 * {@link useMemcached} установлено в значение true), используемый данным
 * компонентом
 * @property array $servers список конфигураций сервера memcache. Каждый
 * элемент - это экземпляр класса {@link CMemCacheServerConfiguration}
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CMemCache.php 3515 2011-12-28 12:29:24Z mdomba $
 * @package system.caching
 * @since 1.0
 */
class CMemCache extends CCache
{
	/**
	 * @var boolean использовать ли {@link http://pecl.php.net/package/memcached memcached}
	 * в качестве базового кэширующего расширения. По умолчанию - false, т.е. использовать
	 * {@link http://pecl.php.net/package/memcache memcache}
	 */
	public $useMemcached=false;
	/**
	 * @var Memcache экземпляр memcache
	 */
	private $_cache=null;
	/**
	 * @var array список конфигураций сервера memcache
	 */
	private $_servers=array();

	/**
	 * Инициализирует данный компонент приложения.
	 * Метод требуется интерфейсом {@link IApplicationComponent}.
	 * Создает экземпляр memcache и добавляет серверы memcache.
	 * @throws CException вызывается, если расширение memcache не загружено или отключено
	 */
	public function init()
	{
		parent::init();
		$servers=$this->getServers();
		$cache=$this->getMemCache();
		if(count($servers))
		{
			foreach($servers as $server)
			{
				if($this->useMemcached)
					$cache->addServer($server->host,$server->port,$server->weight);
				else
					$cache->addServer($server->host,$server->port,$server->persistent,$server->weight,$server->timeout,$server->status);
			}
		}
		else
			$cache->addServer('localhost',11211);
	}

	/**
	 * @return mixed экземпляр memcache (или memcached, если свойство
	 * {@link useMemcached} установлено в значение true), используемый данным
	 * компонентом
	 */
	public function getMemCache()
	{
		if($this->_cache!==null)
			return $this->_cache;
		else
			return $this->_cache=$this->useMemcached ? new Memcached : new Memcache;
	}

	/**
	 * @return array список конфигураций сервера memcache. Каждый элемент - это
	 * экземпляр класса {@link CMemCacheServerConfiguration}
	 */
	public function getServers()
	{
		return $this->_servers;
	}

	/**
	 * @param array $config список конфигураций сервера memcache. Каждый
	 * элемент должен быть массивом со следующими ключами: host, port,
	 * persistent, weight, timeout, retryInterval, status
	 * @see http://www.php.net/manual/en/function.Memcache-addServer.php
	 */
	public function setServers($config)
	{
		foreach($config as $c)
			$this->_servers[]=new CMemCacheServerConfiguration($c);
	}

	/**
	 * Получает значение из кэша по определенному ключу.
	 * Метод переопределяет реализацию класса-родителя.
	 * @param string $key уникальный ключ, идентифицирующий кэшированное значение
	 * @return string хранимое в кэше значение; false, если значения в кэше нет или его срок годности истек
	 */
	protected function getValue($key)
	{
		return $this->_cache->get($key);
	}

	/**
	 * Получает из кэша несколько значений с определенными ключами.
	 * @param array $keys список ключей, идентифицирующих кэшированные значения
	 * @return array список кэшированных значений, индексированный по ключам
	 */
	protected function getValues($keys)
	{
		return $this->useMemcached ? $this->_cache->getMulti($keys) : $this->_cache->get($keys);
	}

	/**
	 * Сохраняет в кэше значение, идентифицируемое ключом.
	 * Метод переопределяет реализацию класса-родителя.
	 * @param string $key ключ, идентифицирующий кэшируемое значение
	 * @param string $value кэшируемое значение
	 * @param integer $expire количество секунд срока годности кэшируемого значения. 0 - без срока годности
	 * @return boolean true, если значение успешно сохранено в кэше, иначе false
	 */
	protected function setValue($key,$value,$expire)
	{
		if($expire>0)
			$expire+=time();
		else
			$expire=0;

		return $this->useMemcached ? $this->_cache->set($key,$value,$expire) : $this->_cache->set($key,$value,0,$expire);
	}

	/**
	 * Сохраняет в кэше значение, идентифицируемое ключом, если кэш не содержит данный ключ.
	 * Метод переопределяет реализацию класса-родителя.
	 * @param string $key ключ, идентифицирующий кэшируемое значение
	 * @param string $value кэшируемое значение
	 * @param integer $expire количество секунд срока годности кэшируемого значения. 0 - без срока годности
	 * @return boolean true, если значение успешно сохранено в кэше, иначе false
	 */
	protected function addValue($key,$value,$expire)
	{
		if($expire>0)
			$expire+=time();
		else
			$expire=0;

		return $this->useMemcached ? $this->_cache->add($key,$value,$expire) : $this->_cache->add($key,$value,0,$expire);
	}

	/**
	 * Удаляет из кеша значение по определенному ключу.
	 * Метод переопределяет реализацию класса-родителя.
	 * @param string $key ключ удаляемого значения
	 * @return boolean true, если в процессе удаления не произошло ошибок
	 */
	protected function deleteValue($key)
	{
		return $this->_cache->delete($key);
	}

	/**
	 * Удаляет все значения из кэша.
	 * Это реализация метода, объявленного в классе-родителе
	 * @return boolean успешно ли выполнилась операция очистки
	 * @since 1.1.5
	 */
	protected function flushValues()
	{
		return $this->_cache->flush();
	}
}

/**
 * CMemCacheServerConfiguration представляет данные конфигурации для отдельного сервера memcache.
 *
 * Обратитесь к {@link http://www.php.net/manual/en/function.Memcache-addServer.php} за 
 * детальными объяснениями по каждому свойству конфигурации.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CMemCache.php 3515 2011-12-28 12:29:24Z mdomba $
 * @package system.caching
 * @since 1.0
 */
class CMemCacheServerConfiguration extends CComponent
{
	/**
	 * @var string имя хоста и IP адрес сервера memcache
	 */
	public $host;
	/**
	 * @var integer порт сервера memcache
	 */
	public $port=11211;
	/**
	 * @var boolean использовать ли постоянное соединение
	 */
	public $persistent=true;
	/**
	 * @var integer вероятность использования данного сервера среди всех серверов
	 */
	public $weight=1;
	/**
	 * @var integer значение, используемое при соединении к серверу, в секундах
	 */
	public $timeout=15;
	/**
	 * @var integer через какое время после ошибки сервера будет восстановлена работоспособность (в секундах)
	 */
	public $retryInterval=15;
	/**
	 * @var boolean должен ли сервер быть помечен как онлайн при ошибке
	 */
	public $status=true;

	/**
	 * Конструктор.
	 * @param array $config список конфигураций сервера memcache
	 * @throws CException вызывается, если конфигурации - не массив
	 */
	public function __construct($config)
	{
		if(is_array($config))
		{
			foreach($config as $key=>$value)
				$this->$key=$value;
			if($this->host===null)
				throw new CException(Yii::t('yii','CMemCache server configuration must have "host" value.'));
		}
		else
			throw new CException(Yii::t('yii','CMemCache server configuration must be an array.'));
	}
}