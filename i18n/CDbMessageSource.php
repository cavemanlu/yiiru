<?php
/**
 * Файл класса CDbMessageSource.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Класс CDbMessageSource представляет источник сообщений, хранящий переведенные сообщения в базе данных.
 *
 * База данных должна содержать 2 таблицы следующих структур:
 * <pre>
 * CREATE TABLE SourceMessage
 * (
 *     id INTEGER PRIMARY KEY,
 *     category VARCHAR(32),
 *     message TEXT
 * );
 * CREATE TABLE Message
 * (
 *     id INTEGER,
 *     language VARCHAR(16),
 *     translation TEXT,
 *     PRIMARY KEY (id, language),
 *     CONSTRAINT FK_Message_SourceMessage FOREIGN KEY (id)
 *          REFERENCES SourceMessage (id) ON DELETE CASCADE ON UPDATE RESTRICT
 * );
 * </pre>
 * Таблица 'SourceMessage' хранит переводимые сообщения, а таблица 'Message' -
 * переведенные сообщения. Имена этих двух таблиц настраиваются свойствами
 * {@link sourceMessageTable} и {@link translatedMessageTable} соответственно.
 *
 * При установленном в любое положительное числовое значение свойстве {@link cachingDuration} сообщения будут кэшироваться.
 *
 * @property CDbConnection $dbConnection соединение БД, используемое для подключения к источнику сообщений
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CDbMessageSource.php 3515 2011-12-28 12:29:24Z mdomba $
 * @package system.i18n
 * @since 1.0
 */
class CDbMessageSource extends CMessageSource
{
	const CACHE_KEY_PREFIX='Yii.CDbMessageSource.';
	/**
	 * @var string идентификатор компонента соединения с БД. По умолчанию - 'db'.
	 */
	public $connectionID='db';
	/**
	 * @var string имя таблицы, содержащей исходные сообщения. По умолчанию - 'SourceMessage'.
	 */
	public $sourceMessageTable='SourceMessage';
	/**
	 * @var string имя таблицы, содержащей переведенные сообщения. По умолчанию - 'Message'.
	 */
	public $translatedMessageTable='Message';
	/**
	 * @var integer срок годности кэша сообщений в секундах.
	 * По умолчанию - 0, т.е., кэш отключен.
	 */
	public $cachingDuration=0;
	/**
	 * @var string идентификатор компонента кэша приложения, используемого для кэширования сообщений.
	 * По умолчанию - 'cache', соответствующий основному компоненту кэша приложения.
	 * Установите данное свойство в значение false для отключения кэширования сообщений
	 */
	public $cacheID='cache';

	/**
	 * Загружает переведенные сообщения для определенного языка и категории
	 * @param string $category категория сообщения
	 * @param string $language целевой язык
	 * @return array загруженные сообщения
	 */
	protected function loadMessages($category,$language)
	{
		if($this->cachingDuration>0 && $this->cacheID!==false && ($cache=Yii::app()->getComponent($this->cacheID))!==null)
		{
			$key=self::CACHE_KEY_PREFIX.'.messages.'.$category.'.'.$language;
			if(($data=$cache->get($key))!==false)
				return unserialize($data);
		}

		$messages=$this->loadMessagesFromDb($category,$language);

		if(isset($cache))
			$cache->set($key,serialize($messages),$this->cachingDuration);

		return $messages;
	}

	private $_db;

	/**
	 * Возвращает соединение БД, используемое для подключения к источнику сообщений
	 * @return CDbConnection соединение БД, используемое для подключения к источнику сообщений
	 * @since 1.1.5
	 */
	public function getDbConnection()
	{
		if($this->_db===null)
		{
			$this->_db=Yii::app()->getComponent($this->connectionID);
			if(!$this->_db instanceof CDbConnection)
				throw new CException(Yii::t('yii','CDbMessageSource.connectionID is invalid. Please make sure "{id}" refers to a valid database application component.',
					array('{id}'=>$this->connectionID)));
		}
		return $this->_db;
	}

	/**
	 * Загружает переведенные сообщения из БД.
	 * Вы можете переопределить данный метод для настройки хранилища сообщений в БД
	 * @param string $category категория сообщения
	 * @param string $language целевой язык
	 * @return array загруженные из БД сообщения
	 * @since 1.1.5
	 */
	protected function loadMessagesFromDb($category,$language)
	{
		$sql=<<<EOD
SELECT t1.message AS message, t2.translation AS translation
FROM {$this->sourceMessageTable} t1, {$this->translatedMessageTable} t2
WHERE t1.id=t2.id AND t1.category=:category AND t2.language=:language
EOD;
		$command=$this->getDbConnection()->createCommand($sql);
		$command->bindValue(':category',$category);
		$command->bindValue(':language',$language);
		$messages=array();
		foreach($command->queryAll() as $row)
			$messages[$row['message']]=$row['translation'];

		return $messages;
	}
}