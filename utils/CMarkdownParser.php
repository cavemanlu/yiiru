<?php
/**
 * Файл класса CMarkdownParser.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

require_once(Yii::getPathOfAlias('system.vendors.markdown.markdown').'.php');
if(!class_exists('HTMLPurifier_Bootstrap',false))
{
	require_once(Yii::getPathOfAlias('system.vendors.htmlpurifier').DIRECTORY_SEPARATOR.'HTMLPurifier.standalone.php');
	HTMLPurifier_Bootstrap::registerAutoload();
}

/**
 * Класс CMarkdownParser - это обертка для {@link http://michelf.com/projects/php-markdown/extra/ MarkdownExtra_Parser}.
 *
 * Класс CMarkdownParser расширяет класс MarkdownExtra_Parser, используя Text_Highlighter
 * для подстветки синтаксиса блоков кода определенного языка.
 * В частности, если блок кода начинается со строки:
 * <pre>
 * [language]
 * </pre>
 * В этом случае будет использоваться подсветка синтаксиса блока кода для установленного языка.
 * Поддерживаемые языки (регистронезависимо):
 * ABAP, CPP, CSS, DIFF, DTD, HTML, JAVA, JAVASCRIPT,
 * MYSQL, PERL, PHP, PYTHON, RUBY, SQL, XML
 *
 * Также можно определить настройки, передаваемые в инструмент подсветки. Например:
 * <pre>
 * [php showLineNumbers=1]
 * </pre>
 * - будут показаны номера строк блока кода.
 *
 * За подробностями о стандарте синтаксиса markdown обратитесь к следующим источникам:
 * <ul>
 * <li>{@link http://daringfireball.net/projects/markdown/syntax официальный синтаксис markdown}</li>
 * <li>{@link http://michelf.com/projects/php-markdown/extra/ расширенный синтаксис markdown}</li>
 * </ul>
 *
 * @property string $defaultCssFile файл CSS по умолчанию, используемый для
 * подсветки блоков кода
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CMarkdownParser.php 3515 2011-12-28 12:29:24Z mdomba $
 * @package system.utils
 * @since 1.0
 */
class CMarkdownParser extends MarkdownExtra_Parser
{
	/**
	 * @var string класс css для элемента div, содержащего
	 * блок кода, подвергающегося подсветке. По умолчанию - 'hl-code'.
	 */
	public $highlightCssClass='hl-code';
	
	/**
	 * @var mixed опции, передаваемые в экземпляр {@link http://htmlpurifier.org HTML Purifier'а}.
	 * Может быть объектом класса HTMLPurifier_Config, массивом директив (Namespace.Directive => Value)
	 * или именем файла настроек.
	 * Данное свойство используется только если вызывается метод {@link safeTransform}.
	 * @see http://htmlpurifier.org/live/configdoc/plain.html
	 * @since 1.1.4
	 */
	public $purifierOptions=null;

	/**
	 * Преобразует содержимое и очищает (при помощи CHtmlPurifier) результат.
	 * Метод вызывает метод transform() для конвертации содержимого с синтаксисом
	 * markdown в HTML-содержимое. Затем используется класс
	 * {@link CHtmlPurifier} для очистки HTML-содержимого во избежание XSS атак.
	 * @param string $content содержиме с синтаксисом markdown
	 * @return string очищенное HTML-содержимое
	 */
	public function safeTransform($content)
	{
		$content=$this->transform($content);
		$purifier=new HTMLPurifier($this->purifierOptions);
		$purifier->config->set('Cache.SerializerPath',Yii::app()->getRuntimePath());
		return $purifier->purify($content);
	}

	/**
	 * @return string файл CSS по умолчанию, используемый для подсветки блоков
	 * кода
	 */
	public function getDefaultCssFile()
	{
		return Yii::getPathOfAlias('system.vendors.TextHighlighter.highlight').'.css';
	}

	/**
	 * Функция обратного вызова при совпадении блока кода.
	 * @param array $matches совпадения
	 * @return string подсвеченный блок кода
	 */
	public function _doCodeBlocks_callback($matches)
	{
		$codeblock = $this->outdent($matches[1]);
		if(($codeblock = $this->highlightCodeBlock($codeblock)) !== null)
			return "\n\n".$this->hashBlock($codeblock)."\n\n";
		else
			return parent::_doCodeBlocks_callback($matches);
	}

	/**
	 * Функция обратного вызова при совпадении обернутого блока кода.
	 * @param array $matches совпадения
	 * @return string подсвеченный блок кода
	 */
	public function _doFencedCodeBlocks_callback($matches)
	{
		return "\n\n".$this->hashBlock($this->highlightCodeBlock($matches[2]))."\n\n";
	}

	/**
	 * Подсвечивает блок кода.
	 * @param string $codeblock блок кода
	 * @return string подсвеченный блок кода. Null, если блок не нуждается в подсветке
	 */
	protected function highlightCodeBlock($codeblock)
	{
		if(($tag=$this->getHighlightTag($codeblock))!==null && ($highlighter=$this->createHighLighter($tag)))
		{
			$codeblock = preg_replace('/\A\n+|\n+\z/', '', $codeblock);
			$tagLen = strpos($codeblock, $tag)+strlen($tag);
			$codeblock = ltrim(substr($codeblock, $tagLen));
			$output=preg_replace('/<span\s+[^>]*>(\s*)<\/span>/', '\1', $highlighter->highlight($codeblock));
			return "<div class=\"{$this->highlightCssClass}\">".$output."</div>";
		}
		else
			return "<pre>".CHtml::encode($codeblock)."</pre>";
	}

	/**
	 * Возвращает пользовательские настройки подсветки.
	 * @param string $codeblock блок кода с настройками подсветки.
	 * @return string пользовательские настройки подсветки. Null, если пользовательских настроек нет.
	 */
	protected function getHighlightTag($codeblock)
	{
		$str = trim(current(preg_split("/\r|\n/", $codeblock,2)));
		if(strlen($str) > 2 && $str[0] === '[' && $str[strlen($str)-1] === ']')
			return $str;
	}

	/**
	 * Создает экземпляр инструмента подсветки.
	 * @param string $options пользовательские настройки
	 * @return Text_Highlighter экземпляр инструмента подсветки
	 */
	protected function createHighLighter($options)
	{
		if(!class_exists('Text_Highlighter', false))
		{
			require_once(Yii::getPathOfAlias('system.vendors.TextHighlighter.Text.Highlighter').'.php');
			require_once(Yii::getPathOfAlias('system.vendors.TextHighlighter.Text.Highlighter.Renderer.Html').'.php');
		}
		$lang = current(preg_split('/\s+/', substr(substr($options,1), 0,-1),2));
		$highlighter = Text_Highlighter::factory($lang);
		if($highlighter)
			$highlighter->setRenderer(new Text_Highlighter_Renderer_Html($this->getHiglightConfig($options)));
		return $highlighter;
	}

	/**
	 * Генерирует конфигурацию для инструмента подсветки.
	 * @param string $options пользовательские настройки
	 * @return array конфигурация для инструмента подсветки
	 */
	public function getHiglightConfig($options)
	{
		$config['use_language'] = true;
		if( $this->getInlineOption('showLineNumbers', $options, false) )
			$config['numbers'] = HL_NUMBERS_LI;
		$config['tabsize'] = $this->getInlineOption('tabSize', $options, 4);
		return $config;
	}

	/**
	 * Получает орпеделенную конфигурацию.
	 * @param string $name имя конфигурации
	 * @param string $str пользовательские настройки
	 * @param mixed $defaultValue значение по умолчанию, если конфигурация не представлена
	 * @return mixed значение конфигурации
	 */
	protected function getInlineOption($name, $str, $defaultValue)
	{
		if(preg_match('/'.$name.'(\s*=\s*(\d+))?/i', $str, $v) && count($v) > 2)
			return $v[2];
		else
			return $defaultValue;
	}
}
