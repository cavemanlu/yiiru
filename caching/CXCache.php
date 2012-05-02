<?php
/**
 * Файл класса CXCache
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Класс CXCache реализует кэш-компонент приложения, основанный на {@link http://xcache.lighttpd.net/ xcache}.
 *
 * Для использования этого компонента приложения должно быть загружено расширение PHP XCache.
 * Функционал очистки кэша (flush) будет корректно работать только если
 * параметр "xcache.admin.enable_auth" файла php.ini установлен в значение
 * "Off".
 *
 * Обратитесь к документации {@link CCache} за информацией об обычных операциях кэша, поддерживаемых компонентом CXCache.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @version $Id: CXCache.php 3568 2012-02-13 16:19:25Z keyboard.idol@gmail.com $
 * @package system.caching
 */
class CXCache extends CCache
{
	/**
	 * Инициализирует данный компонент приложения.
	 * Метод требуется интерфейсом {@link IApplicationComponent}.
	 * Проверяет доступность xcache.
	 * @throws CException вызывается, если расширение xcache не загружено или отключено
	 */
	public function init()
	{
		parent::init();
		if(!function_exists('xcache_isset'))
			throw new CException(Yii::t('yii','CXCache requires PHP XCache extension to be loaded.'));
	}

	/**
	 * Получает значение из кэша по определенному ключу.
	 * Метод переопределяет реализацию класса-родителя.
	 * @param string $key уникальный ключ, идентифицирующий кэшированное значение
	 * @return string хранимое в кэше значение; false, если значения в кэше нет или его срок годности истек
	 */
	protected function getValue($key)
	{
		return xcache_isset($key) ? xcache_get($key) : false;
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
		return xcache_set($key,$value,$expire);
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
		return !xcache_isset($key) ? $this->setValue($key,$value,$expire) : false;
	}

	/**
	 * Удаляет из кеша значение по определенному ключу.
	 * Метод переопределяет реализацию класса-родителя.
	 * @param string $key ключ удаляемого значения
	 * @return boolean true, если в процессе удаления не произошло ошибок
	 */
	protected function deleteValue($key)
	{
		return xcache_unset($key);
	}

	/**
	 * Удаляет все значения из кэша.
	 * Это реализация метода, объявленного в классе-родителе
	 * @return boolean успешно ли выполнилась операция очистки
	 * @since 1.1.5
	 */
	protected function flushValues()
	{
		for($i=0, $max=xcache_count(XC_TYPE_VAR); $i<$max; $i++)
		{
			if(xcache_clear_cache(XC_TYPE_VAR, $i)===false)
				return false;
		}
		return true;
	}
}

