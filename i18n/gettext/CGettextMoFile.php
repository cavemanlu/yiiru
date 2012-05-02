<?php
/**
 * Файл класса CGettextMoFile.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * Класс CGettextMoFile представляет MO-файл Gettext-сообщений.
 *
 * Класс является адаптацией класса Gettext_MO пакета PEAR,
 * написанного Майклом Валнером (Michael Wallner).
 * Обратите внимание на лицензионные условия, приведенные ниже.
 *
 * Copyright (c) 2004-2005, Michael Wallner <mike@iworks.at>.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 *     * Redistributions of source code must retain the above copyright notice,
 *       this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE
 * FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
 * DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
 * SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY,
 * OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CGettextMoFile.php 2798 2011-01-01 19:29:03Z qiang.xue $
 * @package system.i18n.gettext
 * @since 1.0
 */
class CGettextMoFile extends CGettextFile
{
	/**
	 * @var boolean использовать ли порядок байтов "Big Endian" при чтении и записи целых чисел
	 */
	public $useBigEndian=false;

	/**
	 * Конструктор.
	 * @param boolean $useBigEndian использовать ли порядок байтов "Big Endian" при чтении и записи целых чисел
	 */
	public function __construct($useBigEndian=false)
	{
		$this->useBigEndian=$useBigEndian;
	}

	/**
	 * Загружает сообщения из MO-файла.
	 * @param string $file путь к файлу
	 * @param string $context контекст сообщения
	 * @return array перевод сообщений (исходное сообщение => переведенное сообщение)
	 */
	public function load($file,$context)
	{
		if(!($fr=@fopen($file,'rb')))
			throw new CException(Yii::t('yii','Unable to read file "{file}".',
				array('{file}'=>$file)));

		if(!@flock($fr,LOCK_SH))
			throw new CException(Yii::t('yii','Unable to lock file "{file}" for reading.',
				array('{file}'=>$file)));

		$magic=current($array=unpack('c',$this->readByte($fr,4)));
		if($magic==-34)
			$this->useBigEndian=false;
		else if($magic==-107)
			$this->useBigEndian=true;
		else
			throw new CException(Yii::t('yii','Invalid MO file: {file} (magic: {magic}).',
				array('{file}'=>$file,'{magic}'=>$magic)));

		if(($revision=$this->readInteger($fr))!=0)
			throw new CException(Yii::t('yii','Invalid MO file revision: {revision}.',
				array('{revision}'=>$revision)));

		$count=$this->readInteger($fr);
		$sourceOffset=$this->readInteger($fr);
		$targetOffset=$this->readInteger($fr);

		$sourceLengths=array();
		$sourceOffsets=array();
		fseek($fr,$sourceOffset);
		for($i=0;$i<$count;++$i)
		{
			$sourceLengths[]=$this->readInteger($fr);
			$sourceOffsets[]=$this->readInteger($fr);
		}

		$targetLengths=array();
		$targetOffsets=array();
		fseek($fr,$targetOffset);
		for($i=0;$i<$count;++$i)
		{
			$targetLengths[]=$this->readInteger($fr);
			$targetOffsets[]=$this->readInteger($fr);
		}

		$messages=array();
		for($i=0;$i<$count;++$i)
		{
			$id=$this->readString($fr,$sourceLengths[$i],$sourceOffsets[$i]);
			if(($pos=strpos($id,chr(4)))!==false && substr($id,0,$pos)===$context)
			{
				$id=substr($id,$pos+1);
				$message=$this->readString($fr,$targetLengths[$i],$targetOffsets[$i]);
				$messages[$id]=$message;
			}
		}

		@flock($fr,LOCK_UN);
		@fclose($fr);

		return $messages;
	}

	/**
	 * Сохраняет сообщения в MO-файле.
	 * @param string $file путь к файлу
	 * @param array $messages переводы собщений (идентификатор сообщения => перевод сообщения).
	 * Примечание: если сообщение имеет контекст, то идентификатор сообщения должен быть с префиксом
	 * в виде контекста и символом-разделителем - chr(4)
	 */
	public function save($file,$messages)
	{
		if(!($fw=@fopen($file,'wb')))
			throw new CException(Yii::t('yii','Unable to write file "{file}".',
				array('{file}'=>$file)));

		if(!@flock($fw,LOCK_EX))
			throw new CException(Yii::t('yii','Unable to lock file "{file}" for writing.',
				array('{file}'=>$file)));

		// magic
		if($this->useBigEndian)
			$this->writeByte($fw,pack('c*', 0x95, 0x04, 0x12, 0xde));
		else
			$this->writeByte($fw,pack('c*', 0xde, 0x12, 0x04, 0x95));

		// revision
		$this->writeInteger($fw,0);

		// message count
		$n=count($messages);
		$this->writeInteger($fw,$n);

		// offset of source message table
		$offset=28;
		$this->writeInteger($fw,$offset);
		$offset+=($n*8);
		$this->writeInteger($fw,$offset);
		// hashtable size, omitted
		$this->writeInteger($fw,0);
		$offset+=($n*8);
		$this->writeInteger($fw,$offset);

		// length and offsets for source messagess
		foreach(array_keys($messages) as $id)
		{
			$len=strlen($id);
			$this->writeInteger($fw,$len);
			$this->writeInteger($fw,$offset);
			$offset+=$len+1;
		}

		// length and offsets for target messagess
		foreach($messages as $message)
		{
			$len=strlen($message);
			$this->writeInteger($fw,$len);
			$this->writeInteger($fw,$offset);
			$offset+=$len+1;
		}

		// source messages
		foreach(array_keys($messages) as $id)
			$this->writeString($fw,$id);

		// target messages
		foreach($messages as $message)
			$this->writeString($fw,$message);

		@flock($fw,LOCK_UN);
		@fclose($fw);
	}

	/**
	 * Читает один или несколько байтов.
	 * @param resource $fr дескриптор файла
	 * @param integer $n количество считываемых байт
	 * @return string считанные байты
	 */
	protected function readByte($fr,$n=1)
	{
		if($n>0)
			return fread($fr,$n);
	}

	/**
	 * Записывает байты в файл.
	 * @param resource $fw дескриптор файла
	 * @param string $data данные
	 * @return integer количество записанных байтов
	 */
	protected function writeByte($fw,$data)
	{
		return fwrite($fw,$data);
	}

	/**
	 * Читает 4х-байтовое целое число из файла.
	 * @param resource $fr дескриптор файла
	 * @return integer результат
	 * @see useBigEndian
	 */
	protected function readInteger($fr)
	{
		return current($array=unpack($this->useBigEndian ? 'N' : 'V', $this->readByte($fr,4)));
	}

	/**
	 * Записывает 4х-байтовое целое число в файл.
	 * @param resource $fw дескриптор файла
	 * @param integer $data данные
	 * @return integer количество записанных байтов
	 */
	protected function writeInteger($fw,$data)
	{
		return $this->writeByte($fw,pack($this->useBigEndian ? 'N' : 'V', (int)$data));
	}

	/**
	 * Читает строку из файла.
	 * @param resource $fr дескриптор файла
	 * @param integer $length длина строки
	 * @param integer $offset смещение строки в файле. Если null, то чтение начинается с текущей позиции
	 * @return string результат
	 */
	protected function readString($fr,$length,$offset=null)
	{
		if($offset!==null)
			fseek($fr,$offset);
		return $this->readByte($fr,$length);
	}

	/**
	 * Записывает строку в файл.
	 * @param resource $fw дескриптор файла
	 * @param string $data строка
	 * @return integer количество записанных байтов
	 */
	protected function writeString($fw,$data)
	{
		return $this->writeByte($fw,$data."\0");
	}
}
