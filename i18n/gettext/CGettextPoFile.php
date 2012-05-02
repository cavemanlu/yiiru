<?php
/**
 * Файл класса CGettextPoFile.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Класс CGettextPoFile представляет PO-файл Gettext-сообщений.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CGettextPoFile.php 2798 2011-01-01 19:29:03Z qiang.xue $
 * @package system.i18n.gettext
 * @since 1.0
 */
class CGettextPoFile extends CGettextFile
{
	/**
	 * Загружает сообщения из PO-файла.
	 * @param string $file путь к файлу
	 * @param string $context контекст сообщения
	 * @return array перевод сообщений (исходное сообщение => переведенное сообщение)
	 */
	public function load($file,$context)
	{
		$pattern='/(msgctxt\s+"(.*?(?<!\\\\))")?'
			. '\s+msgid\s+"(.*?(?<!\\\\))"'
			. '\s+msgstr\s+"(.*?(?<!\\\\))"/';
		$content=file_get_contents($file);
        $n=preg_match_all($pattern,$content,$matches);
        $messages=array();
        for($i=0;$i<$n;++$i)
        {
        	if($matches[2][$i]===$context)
        	{
	        	$id=$this->decode($matches[3][$i]);
	        	$message=$this->decode($matches[4][$i]);
	        	$messages[$id]=$message;
	        }
        }
        return $messages;
	}

	/**
	 * Сохраняет сообщения в PO-файле.
	 * @param string $file путь к файлу
	 * @param array $messages переводы собщений (идентификатор сообщения => перевод сообщения).
	 * Примечание: если сообщение имеет контекст, то идентификатор сообщения должен быть с префиксом
	 * в виде контекста и символом-разделителем - chr(4)
	 */
	public function save($file,$messages)
	{
		$content='';
		foreach($messages as $id=>$message)
		{
			if(($pos=strpos($id,chr(4)))!==false)
			{
				$content.='msgctxt "'.substr($id,0,$pos)."\"\n";
				$id=substr($id,$pos+1);
			}
			$content.='msgid "'.$this->encode($id)."\"\n";
			$content.='msgstr "'.$this->encode($message)."\"\n\n";
		}
		file_put_contents($file,$content);
	}

	/**
	 * Кодирует специальные символы в сообщении.
	 * @param string $string кодируемое сообщение
	 * @return string кодированное сообщение
	 */
	protected function encode($string)
	{
		return str_replace(array('"', "\n", "\t", "\r"),array('\\"', "\\n", '\\t', '\\r'),$string);
	}

	/**
	 * Декодирует специальные символы в сообщении.
	 * @param string $string декодируемое сообщение
	 * @return string декодированное сообщение
	 */
	protected function decode($string)
	{
		return str_replace(array('\\"', "\\n", '\\t', '\\r'),array('"', "\n", "\t", "\r"),$string);
	}
}