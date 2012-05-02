<?php
/**
 * Файл класса CWinCache
 *
 * @author Alexander Makarov <sam@rmcreative.ru>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Класс CWinCache реализует кэш-компонент приложения, основанный на {@link http://www.iis.net/expand/wincacheforphp WinCache}.
 *
 * Для использования этого компонента приложения должно быть загружено расширение PHP WinCache.
 *
 * Обратитесь к документации {@link CCache} за информацией об обычных операциях кэша, поддерживаемых компонентом WinCache.
 *
 * @author Alexander Makarov <sam@rmcreative.ru>
 * @version $Id: CWinCache.php 2799 2011-01-01 19:31:13Z qiang.xue $
 * @package system.caching
 * @since 1.1.2
 */
class CWinCache extends CCache {
	/**
	 * Инициализирует данный компонент приложения.
	 * Метод требуется интерфейсом {@link IApplicationComponent}.
	 * Проверяет доступность WinCache и кэш пользователя WinCache (параметр wincache.ucenabled файла настроек php.ini).
	 * @throws CException вызывается, если расширение WinCache не загружено или отключено
	 */
	public function init()
	{
		parent::init();
		if(!extension_loaded('wincache'))
			throw new CException(Yii::t('yii', 'CWinCache requires PHP wincache extension to be loaded.'));
		if(!ini_get('wincache.ucenabled'))
			throw new CException(Yii::t('yii', 'CWinCache user cache is disabled. Please set wincache.ucenabled to On in your php.ini.'));
	}

	/**
	 * Получает значение из кэша по определенному ключу.
	 * Метод переопределяет реализацию класса-родителя.
	 * @param string $key уникальный ключ, идентифицирующий кэшированное значение
	 * @return string хранимое в кэше значение; false, если значения в кэше нет или его срок годности истек
	 */
	protected function getValue($key)
	{
		return wincache_ucache_get($key);
	}

	/**
	 * Получает из кэша несколько значений с определенными ключами.
	 * @param array $keys список ключей, идентифицирующих кэшированные значения
	 * @return array список кэшированных значений, индексированный по ключам
	 */
	protected function getValues($keys)
	{
		return wincache_ucache_get($keys);
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
		return wincache_ucache_set($key,$value,$expire);
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
		return wincache_ucache_add($key,$value,$expire);
	}

	/**
	 * Удаляет из кеша значение по определенному ключу.
	 * Метод переопределяет реализацию класса-родителя.
	 * @param string $key ключ удаляемого значения
	 * @return boolean true, если в процессе удаления не произошло ошибок
	 */
	protected function deleteValue($key)
	{
		return wincache_ucache_delete($key);
	}

	/**
	 * Удаляет все значения из кэша.
	 * Это реализация метода, объявленного в классе-родителе
	 * @return boolean успешно ли выполнилась операция очистки
	 * @since 1.1.5
	 */
	protected function flushValues()
	{
		return wincache_ucache_clear();
	}
}