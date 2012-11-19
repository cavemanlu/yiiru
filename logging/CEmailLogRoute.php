<?php
/**
 * Файл класса CEmailLogRoute.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Компонент CEmailLogRoute отправляет выбранные сообщения журнала на email-адреса.
 *
 * Целевые email-адреса могут быть установлены свойством {@link setEmails emails}.
 * Опционально, вы можете установить свойство {@link setSubject subject} (тема письма), 
 * свойство {@link setSentFrom sentFrom} (адрес отправителя) и другие дополнительные заголовки ({@link setHeaders headers}).
 *
 * @property array $emails список установленных адресов назначения
 * @property string $subject установленная тема письма. По умолчанию равно значению CEmailLogRoute::DEFAULT_SUBJECT
 * @property string $sentFrom установленный адрес отправителя
 * @property array $headers дополнительные заголовки, используемые при отправке письма
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package system.logging
 * @since 1.0
 */
class CEmailLogRoute extends CLogRoute
{
	/**
	 * @var boolean set this property to true value in case log data you're going to send through emails contains
	 * non-latin or UTF-8 characters. Emails would be UTF-8 encoded.
	 * @since 1.1.13
	 */
	public $utf8=false;
	/**
	 * @var array список адресов назначения
	 */
	private $_email=array();
	/**
	 * @var string тема письма
	 */
	private $_subject;
	/**
	 * @var string адрес отправителя
	 */
	private $_from;
	/**
	 * @var array список дополнительных заголовков, используемых при отправке письма
	 */
	private $_headers=array();

	/**
	 * Отправляет сообщения журнала по определенным адресам.
	 * @param array $logs список сообщений журнала
	 */
	protected function processLogs($logs)
	{
		$message='';
		foreach($logs as $log)
			$message.=$this->formatLogMessage($log[0],$log[1],$log[2],$log[3]);
		$message=wordwrap($message,70);
		$subject=$this->getSubject();
		if($subject===null)
			$subject=Yii::t('yii','Application Log');
		foreach($this->getEmails() as $email)
			$this->sendEmail($email,$subject,$message);
	}

	/**
	 * Отправляет письмо.
	 * @param string $email отдельный email-адрес
	 * @param string $subject тема письма
	 * @param string $message содержимое письма
	 */
	protected function sendEmail($email,$subject,$message)
	{
		$headers=$this->getHeaders();
		if($this->utf8)
		{
			$headers[]="MIME-Version: 1.0";
			$headers[]="Content-type: text/plain; charset=UTF-8";
			$subject='=?UTF-8?B?'.base64_encode($subject).'?=';
		}
		if(($from=$this->getSentFrom())!==null)
		{
			$matches=array();
			preg_match_all('/([^<]*)<([^>]*)>/iu',$from,$matches);
			if(isset($matches[1][0],$matches[2][0]))
			{
				$name=$this->utf8 ? '=?UTF-8?B?'.base64_encode(trim($matches[1][0])).'?=' : trim($matches[1][0]);
				$from=trim($matches[2][0]);
				$headers[]="From: {$name} <{$from}>";
			}
			else
				$headers[]="From: {$from}";
			$headers[]="Reply-To: {$from}";
		}
		mail($email,$subject,$message,implode("\r\n",$headers));
	}

	/**
	 * @return array список установленных адресов назначения
	 */
	public function getEmails()
	{
		return $this->_email;
	}

	/**
	 * @return mixed $value устанавливаемый список адресов назначения. Если передается строка, предполагается,
	 * что адреса разделены запятой.
	 */
	public function setEmails($value)
	{
		if(is_array($value))
			$this->_email=$value;
		else
			$this->_email=preg_split('/[\s,]+/',$value,-1,PREG_SPLIT_NO_EMPTY);
	}

	/**
	 * @return string установленная тема письма. По умолчанию равно значению CEmailLogRoute::DEFAULT_SUBJECT
	 */
	public function getSubject()
	{
		return $this->_subject;
	}

	/**
	 * @param string $value устанавливаемая тема письма.
	 */
	public function setSubject($value)
	{
		$this->_subject=$value;
	}

	/**
	 * @return string установленный адрес отправителя
	 */
	public function getSentFrom()
	{
		return $this->_from;
	}

	/**
	 * @param string $value устанавливаемый адрес отправителя
	 */
	public function setSentFrom($value)
	{
		$this->_from=$value;
	}

	/**
	 * @return array дополнительные заголовки, используемые при отправке письма
	 * @since 1.1.4
	 */
	public function getHeaders()
	{
		return $this->_headers;
	}

	/**
	 * @param mixed $value список дополнительных заголовков, используемых при отправке письма.
	 * Если переданное значение - строка, она считается списком заголовков, разделенных переводами строк
	 * @since 1.1.4
	 */
	public function setHeaders($value)
	{
		if (is_array($value))
			$this->_headers=$value;
		else
			$this->_headers=preg_split('/\r\n|\n/',$value,-1,PREG_SPLIT_NO_EMPTY);
	}
}

