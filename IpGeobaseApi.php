<?php
/**
 * IpGeobaseApi class file
 * @author Alexander Levin <x8p@leamix.com>
 * @version 0.2
 *
 * Предоставляет доступ к сервису ipgeobase.ru для определения города по IP.
 * Ответ сервиса представляет собой xml-данные, которые преобразуются в ассоциативный массив в родительском классе ServiceApi.
 * @link http://blog.ipgeobase.ru/?p=76
 *
 * Использование:
 * $ipgeo = new IpGeobaseApi;
 * $ipgeo->ip = '144.206.192.6';
 * $data = $ipgeo->ipData;
 * echo $data['ip']['city'];
 * ИЛИ короткий способ:
 * $data = IpGeobaseApi::defineRegion('144.206.192.6');
 * echo $data['ip']['city'];
 *
 * Пример ответа сервиса ipgeobase.ru для $ip='144.206.192.6':
 * <ip-answer>
 *   <ip value="144.206.192.6">
 *     <inetnum>144.206.0.0 - 144.206.255.255</inetnum>
 *     <country>RU</country>
 *     <city>Москва</city>
 *     <region>Москва</region>
 *     <district>Центральный федеральный округ</district>
 *     <lat>55.755787</lat>
 *     <lng>37.617634</lng>
 *   </ip>
 * </ip-answer>
 *
 * Магия:
 * @property array $ipData
 */
class IpGeobaseApi extends ServiceApi
{
	/**
	 * @var string URL севриса
	 */
	protected $url = 'http://ipgeobase.ru:7020/geo';
	/**
	 * @var string IP-адрес
	 */
	public $ip;

	/**
	 * @static
	 * @param string $ip
	 * @param bool $parse
	 * @return array|string
	 */
	public static function defineRegion($ip='', $parse=true)
	{
		$geo = new self;
		$geo->ip = $ip;

		return $geo->getIpData($parse);
	}

	/**
	 * @param bool $parse
	 * @return array|string
	 */
	public function getIpData($parse=true)
	{
		if (!$this->ip)
			$this->ip = $_SERVER['REMOTE_ADDR'];

		$params = array(
			'ip'=>$this->ip,
		);

		return $this->response($params, $this->url, $parse);
	}
}