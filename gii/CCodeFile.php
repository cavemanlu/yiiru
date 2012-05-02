<?php
/**
 * Файл класса CCodeFile.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Класс CCodeFile представляет генерируемый файл с кодом.
 *
 * @property string $relativePath путь к файлу с кодом относительно базового пути приложения
 * @property string $type расширение файла с кодом (например, php, txt)
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CCodeFile.php 3426 2011-10-25 00:01:09Z alexander.makarow $
 * @package system.gii
 * @since 1.1.2
 */
class CCodeFile extends CComponent
{
	const OP_NEW='new';
	const OP_OVERWRITE='overwrite';
	const OP_SKIP='skip';

	/**
	 * @var string путь, по которому должен быть сохранен новый код
	 */
	public $path;
	/**
	 * @var mixed новый сгенерированный код. Если имеет значение null, то свойство {@link path}
	 * должно считаться директорией
	 */
	public $content;
	/**
	 * @var string выполняемая операция
	 */
	public $operation;
	/**
	 * @var string ошибка, появившаяся при сохранении кода в файл
	 */
	public $error;

	/**
	 * Конструктор.
	 * @param string $path путь, по которому должен быть сохранен новый код
	 * @param string $content новый сгенерированный код
	 */
	public function __construct($path,$content)
	{
		$this->path=strtr($path,array('/'=>DIRECTORY_SEPARATOR,'\\'=>DIRECTORY_SEPARATOR));
		$this->content=$content;
		if(is_file($path))
			$this->operation=file_get_contents($path)===$content ? self::OP_SKIP : self::OP_OVERWRITE;
		else if($content===null)  // is dir
			$this->operation=is_dir($path) ? self::OP_SKIP : self::OP_NEW;
		else
			$this->operation=self::OP_NEW;
	}

	/**
	 * Сохраняет код в файл по пути, заданному в свойстве {@link path}.
	 */
	public function save()
	{
		$module=Yii::app()->controller->module;
		if($this->content===null)  // a directory
		{
			if(!is_dir($this->path))
			{
				$oldmask=@umask(0);
				$result=@mkdir($this->path,$module->newDirMode,true);
				@umask($oldmask);
				if(!$result)
				{
					$this->error="Unable to create the directory '{$this->path}'.";
					return false;
				}
			}
			return true;
		}

		if($this->operation===self::OP_NEW)
		{
			$dir=dirname($this->path);
			if(!is_dir($dir))
			{
				$oldmask=@umask(0);
				$result=@mkdir($dir,$module->newDirMode,true);
				@umask($oldmask);
				if(!$result)
				{
					$this->error="Unable to create the directory '$dir'.";
					return false;
				}
			}
		}
		if(@file_put_contents($this->path,$this->content)===false)
		{
			$this->error="Unable to write the file '{$this->path}'.";
			return false;
		}
		else
		{
			$oldmask=@umask(0);
			@chmod($this->path,$module->newFileMode);
			@umask($oldmask);
		}
		return true;
	}

	/**
	 * @return string путь к файлу с кодом относительно базового пути приложения
	 */
	public function getRelativePath()
	{
		if(strpos($this->path,Yii::app()->basePath)===0)
			return substr($this->path,strlen(Yii::app()->basePath)+1);
		else
			return $this->path;
	}

	/**
	 * @return string расширение файла с кодом (например, php, txt)
	 */
	public function getType()
	{
		if(($pos=strrpos($this->path,'.'))!==false)
			return substr($this->path,$pos+1);
		else
			return 'unknown';
	}
}