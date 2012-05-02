<?php
/**
 * Файл класса CApcCache
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Класс CApcCache реализует кэш-компонент приложения, основанный на {@link http://www.php.net/apc APC}.
 * Для использования этого компонента приложения должно быть загружено расширение PHP APC.
 *
 * Обратитесь к документации {@link CCache} за информацией об обычных операциях кэша, поддерживаемых компонентом CApcCache.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CApcCache.php 3515 2011-12-28 12:29:24Z mdomba $
 * @package system.caching
 * @since 1.0
 */
class CApcCache extends CCache
{
	/**
	 * Инициализирует данный компонент приложения.
	 * Метод требуется интерфейсом {@link IApplicationComponent}.
	 * Проверяет доступность APC.
	 * @throws CException вызывается, если расширение APC не загружено или отключено
	 */
	public function init()
	{
		parent::init();
		if(!extension_loaded('apc'))
			throw new CException(Yii::t('yii','CApcCache requires PHP apc extension to be loaded.'));
	}

	/**
	 * Получает значение из кэша по определенному ключу.
	 * Метод переопределяет реализацию класса-родителя.
	 * @param string $key уникальный ключ, идентифицирующий кэшированное значение
	 * @return string хранимое в кэше значение; false, если значения в кэше нет или его срок годности истек
	 */
	protected function getValue($key)
	{
		return apc_fetch($key);
	}

	/**
	 * Получает из кэша несколько значений с определенными ключами.
	 * @param array $keys список ключей, идентифицирующих кэшированные значения
	 * @return array список кэшированных значений, индексированный по ключам
	 */
	protected function getValues($keys)
	{
		return apc_fetch($keys);
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
		return apc_store($key,$value,$expire);
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
		return apc_add($key,$value,$expire);
	}

	/**
	 * Удаляет из кеша значение по определенному ключу.
	 * Метод переопределяет реализацию класса-родителя.
	 * @param string $key ключ удаляемого значения
	 * @return boolean true, если в процессе удаления не произошло ошибок
	 */
	protected function deleteValue($key)
	{
		return apc_delete($key);
	}

	/**
	 * Удаляет все значения из кэша.
	 * Это реализация метода, объявленного в классе-родителе
	 * @return boolean успешно ли выполнилась операция очистки
	 * @since 1.1.5
	 */
	protected function flushValues()
	{
		return apc_clear_cache('user');
	}
}
