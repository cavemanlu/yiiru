<?php
/**
 * Файл класса CJuiWidget.
 *
 * @author Sebastian Thierer <sebathi@gmail.com>
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Класс CJuiWidget - это базовый класс для всех классов виджетов Jquery UI.
 *
 * @author Sebastian Thierer <sebathi@gmail.com>
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CJuiWidget.php 2799 2011-01-01 19:31:13Z qiang.xue $
 * @package zii.widgets.jui
 * @since 1.1
 */
abstract class CJuiWidget extends CWidget
{
	/**
	 * @var string корневой URL-путь, по которому находятся все JavaScript-файлы Jquery UI.
	 * Если даное свойство не установлено (по умолчанию), Yii будет публиковать и использовать пакет
	 * Jquery UI, включенный во фреймворк, и использовать путь к данному пакету в качестве базового пути.
	 * Необходимо установить данное свойство, если желаемая версия пакета Jquery UI отличается от
	 * вклченой во фреймворк. Примечание: по данному URL-адресу должен присутствовать файл,
	 * имя которого определяется свойством {@link scriptFile}. Не добавляйте слэши в конец URL-адреса
	 */
	public $scriptUrl;
	/**
	 * @var string корневой URL-путь, по которому находятся все директории тем Jquery UI.
	 * Если даное свойство не установлено (по умолчанию), Yii будет публиковать и использовать пакет
	 * Jquery UI, включенный во фреймворк, и использовать путь к данному пакету в качестве базового пути.
	 * Необходимо установить данное свойство, если желаемая версия пакета Jquery UI отличается от
	 * вклченой во фреймворк. Примечание: по данному URL-адресу должен присутствовать файл,
	 * имя которого определяется свойством {@link scriptFile}. Не добавляйте слэши в конец URL-адреса
	 */
	public $themeUrl;
	/**
	 * @var string имя темы JUI. По умолчанию - 'base'. Убедитесь, что директория, определяемая свойством {@link themeUrl},
	 * содержит директорию с именем, задаваемым данным свойством (регистрозависимо)
	 */
	public $theme='base';
	/**
	 * @var mixed основной JavaScript-файл пакета JUI. По умолчанию - 'jquery-ui.min.js'.
	 * Примечание: файл должен находиться в директории, определяемой свойством {@link scriptUrl}.
	 * Если необходимо включить несколько файлов скриптов (например, в процессе разработки может понадобиться
	 * включить отдельные файлы скриптов плагинов, а не минимизированный файл скрипта пакета JUI), можно установить
	 * данное свойство массивом имен файлов скриптов. Данное свойство также может быть установлено в значение false,
	 * при этом виджет не будет включать никаких файлов скриптов, и ответственность за их явное включение в каком-либо
	 * другом месте ляжет на разработчика
	 */
	public $scriptFile='jquery-ui.min.js';
	/**
	 * @var mixed имя CSS-файла темы. По умолчанию - 'jquery-ui.css'. Примечание: файл должен
	 * находиться в директории, определяемой свойствами {@link themeUrl} и {@link theme}.
	 * Если необходимо включить несколько CSS-файлов (например, в процессе разработки может понадобиться
	 * включить отдельные CSS-файлы плагинов), можно установить данное свойство массивом имен CSS-файлов.
	 * Данное свойство также может быть установлено в значение false, при этом виджет не будет включать никаких
	 * CSS-файлов темы, и ответственность за их явное включение в каком-либо другом месте ляжет на разработчика
	 */
	public $cssFile='jquery-ui.css';
	/**
	 * @var array начальные JavaScript-опции, которые должны быть переданы в плагин JUI
	 */
	public $options=array();
	/**
	 * @var array HTML-атрибуты тега, представляющего виджет JUI
	 */
	public $htmlOptions=array();

	/**
	 * Инициализирует виджет.
	 * Данный метод публикует ресурсы JUI при необходимости. Также регистрирует JavaScript-файлы
	 * пакетов Jquery и JUI и CSS-файлы темы. Если вы переопределяете данный метод, убедитесь,
	 * что первоначально вызывается родительская реализация
	 */
	public function init()
	{
		$this->resolvePackagePath();
		$this->registerCoreScripts();
		parent::init();
	}

	/**
	 * Определяет путь установки пакета JUI. Данный метод устанавливает корневые
	 * URL-адреса JavaScript-файлов и файлы темы, если они явно не определены.
	 * Метод публикует встроенный пакет JUI и использует путь до него для определения
	 * путей {@link scriptUrl} и {@link themeUrl}
	 */
	protected function resolvePackagePath()
	{
		if($this->scriptUrl===null || $this->themeUrl===null)
		{
			$cs=Yii::app()->getClientScript();
			if($this->scriptUrl===null)
				$this->scriptUrl=$cs->getCoreScriptUrl().'/jui/js';
			if($this->themeUrl===null)
				$this->themeUrl=$cs->getCoreScriptUrl().'/jui/css';
		}
	}

	/**
	 * Регистрирует файлы скриптов ядра. Метод регистрирует JavaScript-файлы 
	 * пакетов Jquery и JUI и CSS-файлы темы
	 */
	protected function registerCoreScripts()
	{
		$cs=Yii::app()->getClientScript();
		if(is_string($this->cssFile))
			$cs->registerCssFile($this->themeUrl.'/'.$this->theme.'/'.$this->cssFile);
		else if(is_array($this->cssFile))
		{
			foreach($this->cssFile as $cssFile)
				$cs->registerCssFile($this->themeUrl.'/'.$this->theme.'/'.$cssFile);
		}

		$cs->registerCoreScript('jquery');
		if(is_string($this->scriptFile))
			$this->registerScriptFile($this->scriptFile);
		else if(is_array($this->scriptFile))
		{
			foreach($this->scriptFile as $scriptFile)
				$this->registerScriptFile($scriptFile);
		}
	}

	/**
	 * Регистрирует JavaScript-файл, определяемый свойством {@link scriptUrl}.
	 * Примечание: по умолчанию код загрузки файла скрипта генерируется в конце страницы для увеличения скорости загрузки страницы
	 * @param string $fileName имя JavaScript-файла
	 * @param integer $position местоположения кода загрузки JavaScript-файла. Допустимые значения:
	 * <ul>
	 * <li>CClientScript::POS_HEAD : код загрузки скрипта вставляется в раздел head прямо перед элементом title;</li>
	 * <li>CClientScript::POS_BEGIN : код загрузки скрипта вставляется в начало раздела body;</li>
	 * <li>CClientScript::POS_END : код загрузки скрипта вставляется в конец раздела body.</li>
	 * </ul>
	 */
	protected function registerScriptFile($fileName,$position=CClientScript::POS_END)
	{
		Yii::app()->getClientScript()->registerScriptFile($this->scriptUrl.'/'.$fileName,$position);
	}
}
