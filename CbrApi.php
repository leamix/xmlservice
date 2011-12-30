<?php
/**
 * CbrApi class file
 * @author Alexander Levin <x8p@leamix.com>
 * @version 0.2
 *
 * Предоставляет доступ к xml-сервису Центробанка РФ http://www.cbr.ru/scripts для получения данных (например по котировкам валют).
 * Ответ сервиса представляет собой xml-данные, которые преобразуются в ассоциативный массив в родительском классе ServiceApi.
 *
 * Примеры запросов с параметрами:
 * @link http://www.cbr.ru/scripts/Root.asp?Prtid=SXML
 *
 * Использование:
 * $cbr = new CbrApi;
 * $data = $cbr->daily(array('date_req'=>date('d/m/Y')));
 * echo 'Доллар США сегодня равен: '.$data['Valute'][9]['Value'];
 *
 * Магия:
 * @method string|array daily
 * @method string|array daily_eng
 * @method string|array dynamic
 * @method string|array ostat
 * @method string|array mkr
 * @method string|array depo
 * @method string|array search
 * @method string|array news
 * @method string|array bic
 * @method string|array swap
 * @method string|array coins
 */
class CbrApi extends ServiceApi
{
	/**
	 * @var string URL севриса
	 */
	protected $url = 'http://www.cbr.ru/scripts/';
	/**
	 * @var string Название сервиса
	 */
	protected $service;

	public function __call($name,$parameters)
	{
		if (key_exists($name, $this->methodsList))
		{
			$url = $this->url.$this->methodsList[$name]['url'];
			$params = $this->checkParams($name, $parameters[0]);
			$parse = isset($parameters[1]) ? $parameters[1] : true;

			return $this->response($params, $url, $parse);
		}
		else
			return parent::__call($name,$parameters);
	}

	public function getMethodsList()
	{
		return array(
			// котировки валют на заданный день date_req
			'daily'=>array(
				'url'=>'XML_daily.asp',
				'params'=>array('date_req'),
			),

			// котировки валют на заданный день date_req, английская версия
			'daily_eng'=>array(
				'url'=>'XML_daily_eng.asp',
				'params'=>array('date_req'),
			),

			// динамика котировок валюты с кодом VAL_NM_RQ в диапазоне дат
			'dynamic'=>array(
				'url'=>'XML_dynamic.asp',
				'params'=>array('date_req1', 'date_req2', 'VAL_NM_RQ'),
			),

			// динамика сведений об остатках средств на корреспондентских счетах кредитных организаций
			'ostat'=>array(
				'url'=>'XML_ostat.asp',
				'params'=>array('date_req1', 'date_req2'),
			),

			// динамика ставок межбанковского рынка
			'mkr'=>array(
				'url'=>'xml_mkr.asp',
				'params'=>array('date_req1', 'date_req2'),
			),

			// динамика ставок привлечения средств по депозитным операциям Банка России на денежном рынке
			'depo'=>array(
				'url'=>'xml_depo.asp',
				'params'=>array('date_req1', 'date_req2'),
			),

			// использование поисковой системы
			'search'=>array(
				'url'=>'XML_search.asp',
				'params'=>array('SearchString'),
			),

			// новости сервера
			'news'=>array(
				'url'=>'XML_News.asp',
				'params'=>array(),
			),

			/**
			 * получение соответствия названий кредитных организаций кодам BIC, где:
			 * bic - код кредитной организации (9 знаков)
			 * name - название (часть названия) кредитной организации
			 * Вы можете указать один или оба параметра.
			 * Если оба параметра отсутствуют, тогда Вы получите полный список соответствия названий кредитных организации и кодов BIC.
			 */
			'bic'=>array(
				'url'=>'XML_bic.asp',
				'params'=>array('name', 'bic'),
			),

			// динамика ставок "валютный своп" - "Валютный своп buy/sell overnight"
			'swap'=>array(
				'url'=>'xml_swap.asp',
				'params'=>array('date_req1', 'date_req2'),
			),

			// динамика отпускных цен Банка России на инвестиционные монеты
			'coins'=>array(
				'url'=>'XMLCoinsBase.asp',
				'params'=>array('date_req1', 'date_req2'),
			),
		);
	}
}