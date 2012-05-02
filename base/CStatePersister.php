<?php
/**
 * Файл содержит класс, реализующий постоянное хранилище данных, основанное на файлах.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Компонент CStatePersister реализует постоянное хранилище данных, основанное на файлах.
 *
 * Он может использоваться для создания доступных в нескольких запросах и сессиях данных.
 *
 * По умолчанию CStatePersister хранит данные в файле 'state.bin', лежащим в
 * {@link CApplication::getRuntimePath рабочей директории} приложения.
 * Вы можете изменить местоположение файла установкой свойства {@link stateFile}.
 *
 * Для получения данных от компонента CStatePersister вызовите метод {@link load()}.
 * Для сохранения - метод {@link save()}.
 *
 * Сравнение между компонентом State Persister, сессиями и кэшем:
 * <ul>
 * <li>session: данные постоянны для одной пользовательской сессии.</li>
 * <li>state persister: данные постоянны для всех запросов/сессий (например, счетчик кликов).</li>
 * <li>cache: непостоянное и быстрое хранилище. Может использоваться как промежуточное хранилище для сессий или постоянного хранилища (state persister).</li>
 * </ul>
 *
 * Т.к. ресурсы сервера часто ограничены, будьте внимательны при использовании компонента CStatePersister
 * для хранения больших объемов данных. Вы также должны рассмотреть вопрос об использовании
 * БД в качестве постоянного хранилища для улучшения пропускной способности.
 *
 * CStatePersister - компонент ядра приложения, используемый для хранения глобального состояния приложения.
 * Получить доступ к нему можно методом {@link CApplication::getStatePersister()}.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CStatePersister.php 3515 2011-12-28 12:29:24Z mdomba $
 * @package system.base
 * @since 1.0
 */
class CStatePersister extends CApplicationComponent implements IStatePersister
{
	/**
	 * @var string путь к файлу, хранящему данные состояния. Убедитесь, что в директория, содержащая файл,
	 * существует и доступна для записи процессом веб-сервера. Если используется относительный путь,
	 * также убедитесь в его корректности.
	 */
	public $stateFile;
	/**
	 * @var string идентификатор кэширующего компонента приложения, используемого для кэширования значений состояния.
	 * По умолчанию - 'cache', т.е. основной кэширующий компонент приложения.
	 * Установите свойство в значение false, если хотите отключить кэширование значений состояния
	 */
	public $cacheID='cache';

	/**
	 * Инициализирует компонент.
	 * Метод переопределяет родительскую реализацию, проверяя, что свойство {@link stateFile}
	 * содержит допустимое значение.
	 */
	public function init()
	{
		parent::init();
		if($this->stateFile===null)
			$this->stateFile=Yii::app()->getRuntimePath().DIRECTORY_SEPARATOR.'state.bin';
		$dir=dirname($this->stateFile);
		if(!is_dir($dir) || !is_writable($dir))
			throw new CException(Yii::t('yii','Unable to create application state file "{file}". Make sure the directory containing the file exists and is writable by the Web server process.',
				array('{file}'=>$this->stateFile)));
	}

	/**
	 * Загружает данные состояния из постоянного хранилища.
	 * @return mixed данные состояния. Null, если данных нет.
	 */
	public function load()
	{
		$stateFile=$this->stateFile;
		if($this->cacheID!==false && ($cache=Yii::app()->getComponent($this->cacheID))!==null)
		{
			$cacheKey='Yii.CStatePersister.'.$stateFile;
			if(($value=$cache->get($cacheKey))!==false)
				return unserialize($value);
			else if(($content=@file_get_contents($stateFile))!==false)
			{
				$cache->set($cacheKey,$content,0,new CFileCacheDependency($stateFile));
				return unserialize($content);
			}
			else
				return null;
		}
		else if(($content=@file_get_contents($stateFile))!==false)
			return unserialize($content);
		else
			return null;
	}

	/**
	 * Сохраняет состояние приложения в постоянное хранилище.
	 * @param mixed $state данные состояния (должны быть сериализуемы)
	 */
	public function save($state)
	{
		file_put_contents($this->stateFile,serialize($state),LOCK_EX);
	}
}
