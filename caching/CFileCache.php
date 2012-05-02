<?php
/**
 * Файл класса CFileCache
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Класс CFileCache реализует кэш-компонент приложения, основанный на файлах.
 *
 * Для каждого значения кэшируемых данных CFileCache будет использовать для хранения отдельные файлы
 * в директории, определяемой свойством {@link cachePath} и равным по умолчанию 'protected/runtime/cache'.
 * Компонент CFileCache будет автоматически выполнять сбор мусора для удаления файлов кэша с истекшим сроком годности.
 *
 * Обратитесь к документации {@link CCache} за информацией об обычных операциях кэша, поддерживаемых компонентом CFileCache.
 *
 * @property integer $gCProbability вероятность (частей на миллион) выполнения
 * "сбора мусора" (GC) при сохранении части данных в кэше. По умолчанию - 100,
 * что означает 0.01% шанс
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CFileCache.php 3515 2011-12-28 12:29:24Z mdomba $
 * @package system.caching
 */
class CFileCache extends CCache
{
	/**
	 * @var string директория хранения файлов кэша. По умолчанию - null, т.е.
	 * использовать в качестве директории 'protected/runtime/cache'.
	 */
	public $cachePath;
	/**
	 * @var string суффикс файла кэша. По умолчанию - '.bin'.
	 */
	public $cacheFileSuffix='.bin';
	/**
	 * @var integer уровень поддиректорий для хранения файлов кэша. По умолчанию - 0, т.е.
	 * поддиректории не используются. Если система имеет большое число файлов кэша (более 10K),
	 * вы можете захотеть установить данное значение в 1 или 2 так, чтобы файловая система не переполнилась.
	 * Значение свойства не должно превышать значение 16 (рекомендуется не более 3).
	 */
	public $directoryLevel=0;

	private $_gcProbability=100;
	private $_gced=false;

	/**
	 * Инициализирует данный компонент приложения.
	 * Метод требуется интерфейсом {@link IApplicationComponent}.
	 * It checks the availability of memcache.
	 * @throws CException if APC cache extension is not loaded or is disabled.
	 */
	public function init()
	{
		parent::init();
		if($this->cachePath===null)
			$this->cachePath=Yii::app()->getRuntimePath().DIRECTORY_SEPARATOR.'cache';
		if(!is_dir($this->cachePath))
			mkdir($this->cachePath,0777,true);
	}

	/**
	 * @return integer вероятность (частей на миллион) выполнения
	 * "сбора мусора" (GC) при сохранении части данных в кэше. По умолчанию -
	 * 100, что означает 0.01% шанс
	 */
	public function getGCProbability()
	{
		return $this->_gcProbability;
	}

	/**
	 * @param integer $value вероятность (частей на миллион) выполнения "сбора мусора" (GC) при сохранении
	 * части данных в кэше. По умолчанию - 100, что означает 0.01% шанс.
	 * Число должно быть в диапазоне 0 и 1000000. Значение 0 означает, что сбор мусора производиться не будет
	 */
	public function setGCProbability($value)
	{
		$value=(int)$value;
		if($value<0)
			$value=0;
		if($value>1000000)
			$value=1000000;
		$this->_gcProbability=$value;
	}

	/**
	 * Удаляет все значения из кэша.
	 * Это реализация метода, объявленного в классе-родителе
	 * @return boolean успешно ли выполнилась операция очистки
	 * @since 1.1.5
	 */
	protected function flushValues()
	{
		$this->gc(false);
		return true;
	}

	/**
	 * Получает значение из кэша по определенному ключу.
	 * Метод переопределяет реализацию класса-родителя.
	 * @param string $key уникальный ключ, идентифицирующий кэшированное значение
	 * @return string хранимое в кэше значение; false, если значения в кэше нет или его срок годности истек
	 */
	protected function getValue($key)
	{
		$cacheFile=$this->getCacheFile($key);
		if(($time=@filemtime($cacheFile))>time())
			return @file_get_contents($cacheFile);
		else if($time>0)
			@unlink($cacheFile);
		return false;
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
		if(!$this->_gced && mt_rand(0,1000000)<$this->_gcProbability)
		{
			$this->gc();
			$this->_gced=true;
		}

		if($expire<=0)
			$expire=31536000; // 1 year
		$expire+=time();

		$cacheFile=$this->getCacheFile($key);
		if($this->directoryLevel>0)
			@mkdir(dirname($cacheFile),0777,true);
		if(@file_put_contents($cacheFile,$value,LOCK_EX)!==false)
		{
			@chmod($cacheFile,0777);
			return @touch($cacheFile,$expire);
		}
		else
			return false;
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
		$cacheFile=$this->getCacheFile($key);
		if(@filemtime($cacheFile)>time())
			return false;
		return $this->setValue($key,$value,$expire);
	}

	/**
	 * Удаляет из кеша значение по определенному ключу.
	 * Метод переопределяет реализацию класса-родителя.
	 * @param string $key ключ удаляемого значения
	 * @return boolean true, если в процессе удаления не произошло ошибок
	 */
	protected function deleteValue($key)
	{
		$cacheFile=$this->getCacheFile($key);
		return @unlink($cacheFile);
	}

	/**
	 * Возвращает путь в файлу кэша по переданному ключу.
	 * @param string $key ключ кэша
	 * @return string путь к файлу кэша
	 */
	protected function getCacheFile($key)
	{
		if($this->directoryLevel>0)
		{
			$base=$this->cachePath;
			for($i=0;$i<$this->directoryLevel;++$i)
			{
				if(($prefix=substr($key,$i+$i,2))!==false)
					$base.=DIRECTORY_SEPARATOR.$prefix;
			}
			return $base.DIRECTORY_SEPARATOR.$key.$this->cacheFileSuffix;
		}
		else
			return $this->cachePath.DIRECTORY_SEPARATOR.$key.$this->cacheFileSuffix;
	}

	/**
	 * Удаляет файлы кэша с истекшим сроком годности.
	 * @param boolean $expiredOnly удалять только файлы кэша с истекшим сроком годности.
	 * Если true, будут удалены все файлы кэша в директории {@link cachePath}.
	 * @param string $path путь для очистки. Если null, будет использоваться путь, заданный свойством {@link cachePath}.
	 */
	public function gc($expiredOnly=true,$path=null)
	{
		if($path===null)
			$path=$this->cachePath;
		if(($handle=opendir($path))===false)
			return;
		while(($file=readdir($handle))!==false)
		{
			if($file[0]==='.')
				continue;
			$fullPath=$path.DIRECTORY_SEPARATOR.$file;
			if(is_dir($fullPath))
				$this->gc($expiredOnly,$fullPath);
			else if($expiredOnly && @filemtime($fullPath)<time() || !$expiredOnly)
				@unlink($fullPath);
		}
		closedir($handle);
	}
}
