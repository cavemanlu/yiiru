<?php
/**
 * Файл класса CFileLogRoute.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Компонент CFileLogRoute записывает сообщения журнала в файлы.
 *
 * Файлы журнала сохраняются в директории, указанной в свойстве {@link setLogPath logPath},
 * и имеют имя, определенное в свойстве {@link setLogFile logFile}. Если размер файла журнала больше,
 * чем указано в свойстве {@link setMaxFileSize maxFileSize} (в килобайтах), запускается процесс ротации,
 * который переименовывает текущий файл журнала, добавляя к нему суффикс
 * '.1'. Все существующие файлы журнала в обратном порядке переименовываются на 1 больше, т.е. '.2' в
 * '.3', '.1' в '.2'. Свойство {@link setMaxLogFiles maxLogFiles}
 * определяет количество файлов журнала.
 *
 * @property string $logPath установленная директория для хранения файлов
 * журнала. По умолчанию - директория времени выполнения приложения
 * @property string $logFile установленное имя файла журнала. По умолчанию -
 * 'application.log'
 * @property integer $maxFileSize максимальный размер файла журнала в
 * килобайтах (KB). По умолчанию - 1024 (1MB)
 * @property integer $maxLogFiles количество файлов, используемых для ротации.
 * По умолчанию - 5
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CFileLogRoute.php 3426 2011-10-25 00:01:09Z alexander.makarow $
 * @package system.logging
 * @since 1.0
 */
class CFileLogRoute extends CLogRoute
{
	/**
	 * @var integer максимальный размер файла журнала
	 */
	private $_maxFileSize=1024; // in KB
	/**
	 * @var integer количество файлов журнала, используемых для ротации
	 */
	private $_maxLogFiles=5;
	/**
	 * @var string директория хранения файлов журнала
	 */
	private $_logPath;
	/**
	 * @var string имя файла журнала
	 */
	private $_logFile='application.log';


	/**
	 * Инициализирует маршрут.
	 * Метод вызывается после создания маршрута менеджером маршрутов.
	 */
	public function init()
	{
		parent::init();
		if($this->getLogPath()===null)
			$this->setLogPath(Yii::app()->getRuntimePath());
	}

	/**
	 * @return string установленная директория для хранения файлов журнала.
	 * По умолчанию - директория времени выполнения приложения
	 */
	public function getLogPath()
	{
		return $this->_logPath;
	}

	/**
	 * @param string $value устанавливаемая директория для хранения файлов журнала
	 * @throws CException вызывается, если путь неверен
	 */
	public function setLogPath($value)
	{
		$this->_logPath=realpath($value);
		if($this->_logPath===false || !is_dir($this->_logPath) || !is_writable($this->_logPath))
			throw new CException(Yii::t('yii','CFileLogRoute.logPath "{path}" does not point to a valid directory. Make sure the directory exists and is writable by the Web server process.',
				array('{path}'=>$value)));
	}

	/**
	 * @return string установленное имя файла журнала. По умолчанию - 'application.log'
	 */
	public function getLogFile()
	{
		return $this->_logFile;
	}

	/**
	 * @param string $value устанавливаемое имя файла журнала
	 */
	public function setLogFile($value)
	{
		$this->_logFile=$value;
	}

	/**
	 * @return integer установленный максимальный размер файла журнала в килобайтах (KB). По умолчанию - 1024 (1MB)
	 */
	public function getMaxFileSize()
	{
		return $this->_maxFileSize;
	}

	/**
	 * @param integer $value устанавливаемый максимальный размер файла журнала в килобайтах (KB)
	 */
	public function setMaxFileSize($value)
	{
		if(($this->_maxFileSize=(int)$value)<1)
			$this->_maxFileSize=1;
	}

	/**
	 * @return integer установленное количество файлов, используемых для ротации. По умолчанию - 5
	 */
	public function getMaxLogFiles()
	{
		return $this->_maxLogFiles;
	}

	/**
	 * @param integer $value устанавливаемое количество файлов, используемых для ротации.
	 */
	public function setMaxLogFiles($value)
	{
		if(($this->_maxLogFiles=(int)$value)<1)
			$this->_maxLogFiles=1;
	}

	/**
	 * Сохраняет сообщения журнала в файлы.
	 * @param array $logs список сообщений журнала
	 */
	protected function processLogs($logs)
	{
		$logFile=$this->getLogPath().DIRECTORY_SEPARATOR.$this->getLogFile();
		if(@filesize($logFile)>$this->getMaxFileSize()*1024)
			$this->rotateFiles();
		$fp=@fopen($logFile,'a');
		@flock($fp,LOCK_EX);
		foreach($logs as $log)
			@fwrite($fp,$this->formatLogMessage($log[0],$log[1],$log[2],$log[3]));
		@flock($fp,LOCK_UN);
		@fclose($fp);
	}

	/**
	 * Производит ротацию файлов журнала.
	 */
	protected function rotateFiles()
	{
		$file=$this->getLogPath().DIRECTORY_SEPARATOR.$this->getLogFile();
		$max=$this->getMaxLogFiles();
		for($i=$max;$i>0;--$i)
		{
			$rotateFile=$file.'.'.$i;
			if(is_file($rotateFile))
			{
				// suppress errors because it's possible multiple processes enter into this section
				if($i===$max)
					@unlink($rotateFile);
				else
					@rename($rotateFile,$file.'.'.($i+1));
			}
		}
		if(is_file($file))
			@rename($file,$file.'.1'); // suppress errors because it's possible multiple processes enter into this section
	}
}
