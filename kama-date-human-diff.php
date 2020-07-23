<?php
/*
 * Plugin Name: Kama Date Human Difference
 * Description: Показывает разницу от текущей даты: 3 часа назад, 5 дней назад, 2 часа назад. Руссифицирует даты в WordPress, переводит месяца и дни недели.
 * Author:      Kama
 * Author url:  http://wp-kama.ru
 * Version:     3.7
 */

new Kama_Date_Human_Diff();

class Kama_Date_Human_Diff {

	public function __construct( $russify_months = true ){

		add_filter( 'date_i18n', [ __CLASS__, 'difference' ], 11, 3 );

		if( $russify_months )
			add_filter( 'date_i18n', [ __CLASS__, 'russify_months' ], 11, 2 );
	}

	/**
	 * Меняет выводимую дату на разницу: 3 часа назад, 5 дней назад, 7 месяцев назад...
	 * Функция для фильтра date_i18n.
	 *
	 * Чтобы функция не работала, в формате нужно использовать обратный слэш \. Пр: "j F\ Y", "\д\а\т\а\: j F Y"
	 *
	 * @param string $date       Исходная дата получаемая из хука, её будем менять.
	 * @param string $req_format Формат даты который передается.
	 * @param int    $from_time  Метка времени от которой нужно считать разница (в UNIX формате).
	 *
	 * @return string Дату в русском формате
	 */
	static function difference( $date, $req_format = '', $from_time = 0, $_to_time = null ){

		// не меняем в админке.
		// выходим, если в формате есть экранированные символы
		if( is_admin() || false !== strpos( $req_format, '\\') )
			return $date;

		$outpatt  = _x( '%s назад','дней назад','km' );
		$cdaypatt = 'j n Y';

		// оптимизация
		static $to_time, $to_day;
		if( ! $to_time ) $to_time = $_to_time ?: current_time( 'timestamp', 0 );
		if( ! $to_day )  $to_day  = date( $cdaypatt, $to_time ); // день мес год без лидирующего нуля: 25 12 2015

		// в формате есть время
		if( preg_match( '/[aABgGhHis]/', $req_format ) ){

			$diff     = $to_time - $from_time; // отдельно да!
			$negative = $diff < 0;

			// если отрицательно
			if( $negative )
				$outpatt = __('через %s','km');

			$min_passed   = floor( $diff/60 )   * ( $negative ? -1 : 1 ) ;
			$hours_passed = floor( $diff/3600 ) * ( $negative ? -1 : 1 ) ;

			if( $min_passed < 60 )
				return '<span title="'. $date .'">'. sprintf( $outpatt, self::_plural($min_passed, __('минута,минуты,минут','km') ) ) .'</span>';

			if( $hours_passed == 0 )
				return '<span title="'. $date .'">'. __('сейчас','km') .'</span>';

			if( $hours_passed < 24 )
				return '<span title="'. $date .'">'. sprintf( $outpatt, self::_plural($hours_passed, __('час,часа,часов','km') ) ) .'</span>';
		}

		// прошло больше 24 часов и в формате есть дата
		if( ( ! isset($hours_passed) || $hours_passed >= 24 ) && preg_match( '/[dDjLNSwz]/', $req_format ) ){

			$diff        = $to_time - $from_time;
			$days_passed = floor( $diff / DAY_IN_SECONDS );

			// если отрицательно
			if( $days_passed < 0 ){
				$days_passed *= -1;
				$outpatt = __('через %s','km');
			}

			if(0){}
			elseif( $days_passed == 0 || date($cdaypatt, $from_time) == $to_day )
				return __('сегодня','km');
			elseif( $days_passed == 1 )
				return __('вчера','km');
			// дни
			elseif( $days_passed < 30 ){
				$out = self::_plural( $days_passed, __('день,дня,дней','km') );
			}
			// месяцы
			elseif( $days_passed < 365 ){
				$months_passed = floor( $days_passed / 30.5 ) ?: 1;
				$outpatt       = "<span title=\"$date\">$outpatt</span>";
				$is_short_patt = '/(?<!\\\\)[DM]/'; // в формате есть D или M (короткий день недели или месяц)

				if( preg_match( $is_short_patt, $req_format ) )
					$out = $months_passed == 1 ? __('месяц','km') : "$months_passed ". _x('мес','месяц','km');
				else
					$out = $months_passed == 1 ? __('месяц','km') : self::_plural( $months_passed, __('месяц,месяца,месяцев','km') );
			}
			// годы
			elseif( $days_passed >= 365 ){
				$years_passed  = floor( $days_passed / (30.5 * 12) ) ?: 1; // лет прошло
				$months_passed = floor( ( $days_passed - ($years_passed*365) ) / 30.5 ) ?: 1; // месяцев прошло
				$ten_m_part    = intval( round($months_passed/12, 2) * 10 ); // десятая часть месяца
				$ten_m_part    = $ten_m_part ? ".$ten_m_part" : '';

				$outpatt = "<span title=\"$date\">$outpatt</span>";

				// без числа вначале
				if( $years_passed == 1 && ! $ten_m_part )
					$out = __('год','km');
				else {
					if( $years_passed == 1 && $ten_m_part )
						$out = "$years_passed$ten_m_part ". _x('года','1.5 года назад','km');
					else
						$out = "$years_passed$ten_m_part ". self::_plural( $years_passed, __('год,года,лет','km'), true );
				}

			}

			return sprintf( $outpatt, $out );
		}

		// если это время, год, штамп времени или ..., то просто возвращаем результат
		return $date;
	}

	/**
	 * Русифицирует месяца и недели в дате.
	 * Функция для фильтра date_i18n.
	 *
	 * @param string $date       Дата в принятом формате.
	 * @param string $req_format Формат передаваемой даты.
	 *
	 * @return string Дату в русском формате.
	 */
	static function russify_months( $date, $req_format ){

		// в формате есть "строковые" неделя или месяц. выходим, если в формате есть экранированные символы
		if( false !== strpos( $req_format, '\\') || ! preg_match('/[FMlS]/', $req_format ) || determine_locale() !== 'ru_RU'  )
			return $date;

		$date = strtr( $date, [
			'Январь'=>'января', 'Февраль'=>'февраля', 'Март'=>'марта', 'Апрель'=>'апреля', 'Май'=>'мая', 'Июнь'=>'июня', 'Июль'=>'июля', 'Август'=>'августа', 'Сентябрь'=>'сентября', 'Октябрь'=>'октября', 'Ноябрь'=>'ноября', 'Декабрь'=>'декабря',

			'Янв'=>'янв.', 'Фев'=>'фев.', 'Мар'=>'март', 'Апр'=>'апр.', 'Июн'=>'июнь', 'Июл'=>'июль', 'Авг'=>'авг.', 'Сен'=>'сен.', 'Окт'=>'окт.', 'Ноя'=>'ноя.', 'Дек'=>'дек.',

			'January'=>'января', 'February'=>'февраля', 'March'=>'марта', 'April'=>'апреля', 'May'=>'мая', 'June'=>'июня', 'July'=>'июля', 'August'=>'августа', 'September'=>'сентября', 'October'=>'октября', 'November'=>'ноября', 'December'=>'декабря',

			'Jan'=>'янв.', 'Feb'=>'фев.', 'Mar'=>'март.', 'Apr'=>'апр.', 'Jun'=>'июня', 'Jul'=>'июля', 'Aug'=>'авг.', 'Sep'=>'сен.', 'Oct'=>'окт.', 'Nov'=>'нояб.', 'Dec'=>'дек.',

			'Sunday'=>'воскресенье', 'Monday'=>'понедельник', 'Tuesday'=>'вторник', 'Wednesday'=>'среда', 'Thursday'=>'четверг', 'Friday'=>'пятница', 'Saturday'=>'суббота',

			'Sun'=>'вос.', 'Mon'=>'пон.', 'Tue'=>'вт.', 'Wed'=>'ср.', 'Thu'=>'чет.', 'Fri'=>'пят.', 'Sat'=>'суб.', 'th'=>'', 'st'=>'', 'nd'=>'', 'rd'=>'',
		] );

		return $date;
	}

	/**
	 * Склонение. Возвращает переданное число и слово после него в нужном склонении.
	 *
	 * @param      $number
	 * @param      $titles
	 * @param bool $strip_num
	 *
	 * @return string
	 */
	static function _plural( $number, $titles, $strip_num = false ){
		$titles = array_map( 'trim', explode( ',', $titles ) );
		$cases = [ 2, 0, 1, 1, 1, 2 ];
		return ( $strip_num ? '' : $number .' ' ). $titles[ ($number%100 > 4 && $number %100 < 20) ? 2 : $cases[min($number%10, 5)] ];
	}

}

/*
Изменения:
3.7 - добавил: обработку минут.
    - изменил: название класса с Kama_Date_Russify на Kama_Date_Human_Diff.
    - небольшой рефакторинг.
3.2 - добавил: десятая часть года: "1.2 года назад". "<span title="date"" для месяцев.
3.1 - баг: "год назад" не отображалось.
2.6 - добавил: годы (пример: 2 года назад). Небольшой рефакторинг.
2.5 - добавил: для времени спан со реальным временеи когда вместо времени слово: сейчас, 2 часа назад.
	  добавил: не работает в админке
2.4 - баг: 0 дней назад (а надо сегодня)
2.3 - сегодня показывалось если совпадает просто дата месяца, сделал чтобы совпадали еще и день месяца и год...
*/

