<?php
/**
 * Файл содержит класс, реализующий функцию менеджера безопасности.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * CSecurityManager обеспечивает функции секретных ключей, хеширования и
 * шифрование.
 *
 * CSecurityManager используется компонентами Yii и приложением для целей,
 * связанных с безопасностью. Например, он используется в функции проверки куки
 * (cookie) для предотвращения подделки данных cookie.
 *
 * В основном, CSecurityManager используется для защиты данных от подделки и
 * просмотра. Он может генерировать {@link http://ru.wikipedia.org/wiki/HMAC HMAC}
 * (hash message authentication code, хеш-код идентификации сообщений) и
 * шифровать данные. Секретный ключ, используемый для генерации HMAC,
 * устанавливается свойством {@link setValidationKey ValidationKey}. Ключ,
 * используемый для шифрования данных, устанавливается свойством
 * {@link setEncryptionKey EncryptionKey}. Если эти ключи не установлены явно,
 * генерируются и используются случайные ключи.
 *
 * Для защиты данных с использованием HMAC, вызовите метод {@link hashData()}; а для проверки,
 * подделаны ли данные, вызовите метод {@link validateData()}, который возвращает реальные данные,
 * если они не были подделаны. Алгоритм, используемый для генерации HMAC, определяется свойством
 * {@link validation}.
 *
 * Для шифрования и дешифровки данных используется методы {@link encrypt()} и {@link decrypt()}
 * соответственно, которые используют алгоритм шифрования 3DES. Примечание: должно быть
 * установлено и загружено расширение PHP Mcrypt.
 *
 * CSecurityManager - это компонент ядра приложения, доступный методом
 * {@link CApplication::getSecurityManager()}.
 *
 * @property string $validationKey секретный ключ, используемый для генерации
 * HMAC. Если ключ явно не установлен, будет сгенерирован и возвращен новый
 * случайный ключ
 * @property string $encryptionKey секретный ключ, используемый для
 * шифрования/дешифровки данных. Если ключ явно не установлен, будет
 * сгенерирован и возвращен новый случайный ключ
 * @property string $validation
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: CSecurityManager.php 3555 2012-02-09 10:29:44Z mdomba $
 * @package system.base
 * @since 1.0
 */
class CSecurityManager extends CApplicationComponent
{
	const STATE_VALIDATION_KEY='Yii.CSecurityManager.validationkey';
	const STATE_ENCRYPTION_KEY='Yii.CSecurityManager.encryptionkey';

	/**
	 * @var string название алгоритма хэширования, используемого в методе {@link computeHMAC}.
	 * См. {@link http://php.net/manual/en/function.hash-algos.php hash-algos} - список возможных
	 * алгоритмов хэширования. Помните, что при использовании PHP версии 5.1.1 или ниже вы можете использовать только
	 * алгоритмы 'sha1' или 'md5'.
	 *
	 * По умолчанию - 'sha1', т.е., используется алгоритм хэширования SHA1
	 * @since 1.1.3
	 */
	public $hashAlgorithm='sha1';
	/**
	 * @var mixed название алгоритма шифрования, используемого в методах {@link encrypt} и {@link decrypt}.
	 * Передается первым параметром в функцию {@link http://php.net/manual/en/function.mcrypt-module-open.php mcrypt_module_open}.
	 *
	 * Свойство также может быть задано как массив. В этом случае элементы массива будут переданы по порядку в качестве
	 * параметров в функцию mcrypt_module_open. Например, массив <code>array('rijndael-256', '', 'ofb', '')</code>.
	 *
	 * По умолчанию - 'des', т.е., используется алгоритм шифрования DES
	 * @since 1.1.3
	 */
	public $cryptAlgorithm='des';

	private $_validationKey;
	private $_encryptionKey;
	private $_mbstring;

	public function init()
	{
		parent::init();
		$this->_mbstring=extension_loaded('mbstring');
	}

	/**
	 * @return string генерирует случайный частный ключ
	 */
	protected function generateRandomKey()
	{
		return sprintf('%08x%08x%08x%08x',mt_rand(),mt_rand(),mt_rand(),mt_rand());
	}

	/**
	 * @return string секретный ключ, используемый для генерации HMAC.
	 * Если ключ явно не установлен, будет сгенерирован и возвращен новый случайный ключ.
	 */
	public function getValidationKey()
	{
		if($this->_validationKey!==null)
			return $this->_validationKey;
		else
		{
			if(($key=Yii::app()->getGlobalState(self::STATE_VALIDATION_KEY))!==null)
				$this->setValidationKey($key);
			else
			{
				$key=$this->generateRandomKey();
				$this->setValidationKey($key);
				Yii::app()->setGlobalState(self::STATE_VALIDATION_KEY,$key);
			}
			return $this->_validationKey;
		}
	}

	/**
	 * @param string $value ключ, используемый при генерации HMAC
	 * @throws CException вызывается, если ключ пустой
	 */
	public function setValidationKey($value)
	{
		if(!empty($value))
			$this->_validationKey=$value;
		else
			throw new CException(Yii::t('yii','CSecurityManager.validationKey cannot be empty.'));
	}

	/**
	 * @return string секретный ключ, используемый для шифрования/дешифровки данных.
	 * Если ключ явно не установлен, будет сгенерирован и возвращен новый случайный ключ.
	 */
	public function getEncryptionKey()
	{
		if($this->_encryptionKey!==null)
			return $this->_encryptionKey;
		else
		{
			if(($key=Yii::app()->getGlobalState(self::STATE_ENCRYPTION_KEY))!==null)
				$this->setEncryptionKey($key);
			else
			{
				$key=$this->generateRandomKey();
				$this->setEncryptionKey($key);
				Yii::app()->setGlobalState(self::STATE_ENCRYPTION_KEY,$key);
			}
			return $this->_encryptionKey;
		}
	}

	/**
	 * @param string $value секретный ключ, используемый для шифрования/дешифровки данных.
	 * @throws CException вызывается, если ключ пустой
	 */
	public function setEncryptionKey($value)
	{
		if(!empty($value))
			$this->_encryptionKey=$value;
		else
			throw new CException(Yii::t('yii','CSecurityManager.encryptionKey cannot be empty.'));
	}

	/**
	 * Метод считается устаревшим с версии 1.1.3.
	 * Исользуйте вместо него {@link hashAlgorithm}
	 * @return string
	 */
	public function getValidation()
	{
		return $this->hashAlgorithm;
	}

	/**
	 * Метод считается устаревшим с версии 1.1.3.
	 * Исользуйте вместо него {@link hashAlgorithm}
	 */
	public function setValidation($value)
	{
		$this->hashAlgorithm=$value;
	}

	/**
	 * Шифрует данные
	 * @param string $data шифруемые данные
	 * @param string $key ключ шифрования. По умолчанию - null, т.е., используется {@link getEncryptionKey EncryptionKey}
	 * @return string шифрованные данные
	 * @throws CException вызывается, если расширение PHP Mcrypt не загружено
	 */
	public function encrypt($data,$key=null)
	{
		$module=$this->openCryptModule();
		$key=$this->substr($key===null ? md5($this->getEncryptionKey()) : $key,0,mcrypt_enc_get_key_size($module));
		srand();
		$iv=mcrypt_create_iv(mcrypt_enc_get_iv_size($module), MCRYPT_RAND);
		mcrypt_generic_init($module,$key,$iv);
		$encrypted=$iv.mcrypt_generic($module,$data);
		mcrypt_generic_deinit($module);
		mcrypt_module_close($module);
		return $encrypted;
	}

	/**
	 * Дешифрует данные
	 * @param string $data дешифруемые данные
	 * @param string $key ключ шифрования. По умолчанию - null, т.е., используется {@link getEncryptionKey EncryptionKey}
	 * @return string дешифрованные данные
	 * @throws CException вызывается, если расширение PHP Mcrypt не загружено
	 */
	public function decrypt($data,$key=null)
	{
		$module=$this->openCryptModule();
		$key=$this->substr($key===null ? md5($this->getEncryptionKey()) : $key,0,mcrypt_enc_get_key_size($module));
		$ivSize=mcrypt_enc_get_iv_size($module);
		$iv=$this->substr($data,0,$ivSize);
		mcrypt_generic_init($module,$key,$iv);
		$decrypted=mdecrypt_generic($module,$this->substr($data,$ivSize,$this->strlen($data)));
		mcrypt_generic_deinit($module);
		mcrypt_module_close($module);
		return rtrim($decrypted,"\0");
	}

	/**
	 * Открывает модуль mcrypt с конфигурацией, определенной в {@link cryptAlgorithm}.
	 * @return resource дескриптор модуля mycrypt
	 * @since 1.1.3
	 */
	protected function openCryptModule()
	{
		if(extension_loaded('mcrypt'))
		{
			if(is_array($this->cryptAlgorithm))
				$module=@call_user_func_array('mcrypt_module_open',$this->cryptAlgorithm);
			else
				$module=@mcrypt_module_open($this->cryptAlgorithm,'', MCRYPT_MODE_CBC,'');

			if($module===false)
				throw new CException(Yii::t('yii','Failed to initialize the mcrypt module.'));

			return $module;
		}
		else
			throw new CException(Yii::t('yii','CSecurityManager requires PHP mcrypt extension to be loaded in order to use data encryption feature.'));
	}

	/**
	 * Добавляет префикс в виде HMAC к данным.
	 * @param string $data хешируемые данные.
	 * @param string $key частный ключ, испльзуемый для генерации HMAC. По умолчанию - null, т.е., используется {@link validationKey}
	 * @return string данные, с префиксом в виде HMAC
	 */
	public function hashData($data,$key=null)
	{
		return $this->computeHMAC($data,$key).$data;
	}

	/**
	 * Проверяет, поддельные ли данные.
	 * @param string $data проверяемые данные. Данные должны быть предварительно сгенерированы
	 * методом {@link hashData()}.
	 * @param string $key частный ключ, испльзуемый для генерации HMAC. По умолчанию - null, т.е., используется {@link validationKey}
	 * @return string реальные данные с префиксом в виде HMAC. False, если данные подделаны
	 */
	public function validateData($data,$key=null)
	{
		$len=$this->strlen($this->computeHMAC('test'));
		if($this->strlen($data)>=$len)
		{
			$hmac=$this->substr($data,0,$len);
			$data2=$this->substr($data,$len,$this->strlen($data));
			return $hmac===$this->computeHMAC($data2,$key)?$data2:false;
		}
		else
			return false;
	}

	/**
	 * Вычисляет HMAC для данных, используя {@link getValidationKey ValidationKey}.
	 * @param string $data данные, для которых должен быть сгенерирован HMAC
	 * @param string $key частный ключ, испльзуемый для генерации HMAC. По умолчанию - null, т.е., используется {@link validationKey}
	 * @return string HMAC для данных
	 */
	protected function computeHMAC($data,$key=null)
	{
		if($key===null)
			$key=$this->getValidationKey();

		if(function_exists('hash_hmac'))
			return hash_hmac($this->hashAlgorithm, $data, $key);

		if(!strcasecmp($this->hashAlgorithm,'sha1'))
		{
			$pack='H40';
			$func='sha1';
		}
		else
		{
			$pack='H32';
			$func='md5';
		}
		if($this->strlen($key) > 64)
			$key=pack($pack, $func($key));
		if($this->strlen($key) < 64)
			$key=str_pad($key, 64, chr(0));
		$key=$this->substr($key,0,64);
		return $func((str_repeat(chr(0x5C), 64) ^ $key) . pack($pack, $func((str_repeat(chr(0x36), 64) ^ $key) . $data)));
	}

	/**
	 * Возвращает длину переданной строки. Если доступно расширение "mbstring",
	 * то использует функцию "mb_strlen", иначе - "strlen"
	 * @param string $string строка, длина которой измеряется
	 * @return int длина переданной строки
	 */
	private function strlen($string)
	{
		return $this->_mbstring ? mb_strlen($string,'8bit') : strlen($string);
	}

	/**
	 * Возвращает часть переданной строки по начальной позиции и длины
	 * подстроки. Если доступно расширение "mbstring", то использует функцию 
	 * "mb_substr", иначе - "substr"
	 * @param string $string входная строка. Должна состоять как минимум из
	 * одного символа
	 * @param int $start стартовая позиция подстроки
	 * @param int $length длина подстроки
	 * @return string подстрока; false - при ошибке или пустой строке
	 */
	private function substr($string,$start,$length)
	{
		return $this->_mbstring ? mb_substr($string,$start,$length,'8bit') : substr($string,$start,$length);
	}
}
