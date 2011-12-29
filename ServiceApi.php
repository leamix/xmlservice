<?php
/**
 * ServiceApi abstract class file
 * @author Alexander Levin <x8p@leamix.com>
 * @version 0.2
 *
 * Базовый класс для создания интерфейсов доступа к xml-данным различных сервисов.
 *
 * Магия:
 * @property string $latestUrl последний запрошенный URL со всем параметрами
 * @property array $methodsList массив вариаций запроса
 */
abstract class ServiceApi extends CComponent
{
	/**
	 * @var string URL сервиса. Должен быть определен в классе-наследнике
	 */
	protected $url;
	/**
	 * @var string
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
			throw new CException('URL '.$this->_latestUrl.' is not responding.');

		return $parse ? $this->parseResponse($response) : $response;
	}

	/**
	 * Преобразовывает строку с xml-данными в ассоциативный массив.
	 * Хитрый способ преобразования найден на php.net {@link http://ru.php.net/manual/en/function.simplexml-load-string.php#102277}
	 *
	 * @param string $xmldata строка с xml
	 * @return array
	 */
	public function parseResponse($xmldata) {
		return json_decode(json_encode(simplexml_load_string($xmldata)),1);
	}

	/**
	 * Возвращет массив вариаций запроса в формате
	 * array(
	 *     'method_name_1'=>array(
	 *         'url'=>'/some_url_ending_1.php',
	 *         'params'=>array('param1', 'param2', 'param3')
	 *     ),
	 *     'method_name_2'=>array(
	 *         'url'=>'/some_url_ending_2.php',
	 *         'params'=>array('param1', 'param2', 'param3')
	 *     )
	 * );
	 * @return array
	 */
	public function getMethodsList() {
		return array();
	}

	/**
	 * Сверяет массив переданных параметров с массивом допустимых параметров метода, возвращает только нужные
	 * @param string $name название метода из getMethodsList()
	 * @param array $array параметры для проверки
	 * @return array
	 */
	protected function checkParams($name, $array)
	{
		$data = array();
		if ($name && $array)
		{
			$method = $this->methodsList[$name]['params'];
			foreach ($array as $key=>$p)
			{
				if (in_array($key, $method))
					$this->checkParamType($data, $key, $p, $name);
			}
		}

		return $data;
	}

	/**
	 * Выполняет проверку параметров и преобразовывает в требуемый формат
	 * @param array $data ссылка на окончательный массив параметров
	 * @param string $key ключ массива - имя параметра
	 * @param mixed $param значение проверяемого параметра
	 * @param string $methodname имя матода из getMethodsList()
	 * @return mixed
	 */
	protected function checkParamType(&$data, $key, $param, $methodname) {
		$data[$key] = $param;
	}
}