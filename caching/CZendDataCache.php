<?php
/**
 * Файл класса CZendDataCache.
 *
 * @author Steffen Dietz <steffo.dietz[at]googlemail[dot]com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Класс CZendDataCache реализует кэш-компонент приложения, основанный на Zend Data Cache и
 * поставляемый с {@link http://www.zend.com/en/products/server/ ZendServer}.
 *
 * Для использования данного компонента приложения должно быть загружено расширение PHP Zend Data Cache.
 *
 * Обратитесь к документации {@link CCache} за информацией об обычных операциях кэша, поддерживаемых компонентом CZendDataCache.
 *
 * @author Steffen Dietz <steffo.dietz[at]googlemail[dot]com>
 * @version $Id: CZendDataCache.php 3515 2011-12-28 12:29:24Z mdomba $
 * @package system.caching
 */
class CZendDataCache extends CCache
{
	/**
	 * Инициализирует данный компонент приложения.
	 * Метод требуется интерфейсом {@link IApplicationComponent}.
	 * Проверяет доступность Zend Data Cache.
	 * @throws CException если расширение Zend Data Cache не загружено.
	 */
	public function init()
	{
		parent::init();
		if(!function_exists('zend_shm_cache_store'))
			throw new CException(Yii::t('yii','CZendDataCache requires PHP Zend Data Cache extension to be loaded.'));
	}

	/**
	 * Получает значение из кэша по определенному ключу.
	 * Метод переопределяет реализацию класса-родителя.
	 * @param string $key уникальный ключ, идентифицирующий кэшированное значение
	 * @return string хранимое в кэше значение; false, если значения в кэше нет или его срок годности истек
	 */
	protected function getValue($key)
	{
		$result = zend_shm_cache_fetch($key);
		return $result !== NULL ? $result : false;
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
		return zend_shm_cache_store($key,$value,$expire);
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
		return (NULL === zend_shm_cache_fetch($key)) ? $this->setValue($key,$value,$expire) : false;
	}

	/**
	 * Удаляет из кеша значение по определенному ключу.
	 * Метод переопределяет реализацию класса-родителя.
	 * @param string $key ключ удаляемого значения
	 * @return boolean true, если в процессе удаления не произошло ошибок
	 */
	protected function deleteValue($key)
	{
		return zend_shm_cache_delete($key);
	}

	/**
	 * Удаляет все значения из кэша.
	 * Это реализация метода, объявленного в классе-родителе
	 * @return boolean успешно ли выполнилась операция очистки
	 * @since 1.1.5
	 */
	protected function flushValues()
	{
		return zend_shm_cache_clear();
	}
}
