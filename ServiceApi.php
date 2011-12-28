<?php
/**
 * ServiceApi abstract class file
 * @author Alexander Levin <x8p@leamix.com>
 * @version 0.2
 *
 * Базовый класс для создания интерфейсов доступа к xml-данным различных сервисов.
 *
 * Магия:
 * @property array $methodsList
 * @property string $latestUrl
 */
abstract class ServiceApi extends CComponent
{
	/**
	 * @var string URL сервиса. Должен быть определен в классе-наследнике
	 */
	protected $url;
	/**
	 * @var string последний запрошенный URL со всем параметрами
	 */
	protected $_latestUrl;

	public function __construct()
	{
		if (!$this->url)
			throw new CException('The URL should be specified in '.get_class($this).' class file.');
	}

	public function getLatestUrl() {
		return $this->_latestUrl;
	}

	/**
	 * Возвращает массив с заголовками для отправки сервису.
	 * @return array
	 */
	protected function getHeaders()
	{
		$host = 'http://'.str_replace('www','',$_SERVER['HTTP_HOST']);
		return array(
			'http'=>array(
				'method'=>"GET",
				'header'=>"Referer: ".$host."\r\n"
			)
		);
	}

	/**
	 * Отправляет запрос на указанный $url с параметрами $params.
	 * Если $url не указан, запрос отправляется на адрес, указанный в атрибуте класса $this->url.
	 * Третий параметр отвечает за формат выдачи.
	 * Если установлен false, метод вернет строку, содержащую ответ сервиса без преобразований, иначе вернет массив.
	 *
	 * @param array $params
	 * @param string $url
	 * @param bool $parse
	 * @return array|string
	 */
	protected function response($params=array(), $url='', $parse=true)
	{
		if ($url==='')
			$url = $this->url;

		if (!empty($params))
			$data = '?'.Yii::app()->getUrlManager()->createPathInfo($params,'=','&');
		else
			$data = '';

		if (YII_DEBUG)
			Yii::trace('Creating request to '.$url, get_class($this));

		$response = @file_get_contents(
			$this->_latestUrl = $url.$data, null,
			stream_context_create($this->getHeaders())
		);

		if ($response === false)
			throw new CException('URL '.$this->url.' is not responding.');

		return $parse ? $this->parseResponse($response) : $response;
	}

	/**
	 * Преобразовывает строку с xml-данными в ассоциативный массив.
	 * Хитрый способ преобразования найден на php.net {@link http://ru.php.net/manual/en/function.simplexml-load-string.php#102277}
	 *
	 * @param string $xmldata
	 * @return array
	 */
	public function parseResponse($xmldata) {
		return json_decode(json_encode(simplexml_load_string($xmldata)),1);
	}

	/**
	 * @return array
	 */
	public function getMethodsList() {
		return array();
	}

	/**
	 * @param string $name
	 * @param array $array
	 * @return array
	 */
	public function checkParams($name, $array)
	{
		$data = array();
		if ($name && $array)
		{
			$method = $this->methodsList[$name]['params'];
			foreach ($array as $key=>$p)
			{
				if (in_array($key, $method))
					$data[$key] = $this->checkParamType($p);
			}
		}

		return $data;
	}

	/**
	 * @return mixed
	 */
	protected function checkParamType($param) {
		return $param;
	}
}