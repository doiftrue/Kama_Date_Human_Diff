<?php

/*
 * Plugin Name: Kama Date Human Difference
 * Description: Показывает разницу от текущей даты: 3 часа назад, 5 дней назад, 2 часа назад. Руссифицирует даты в WordPress, переводит месяца и дни недели.
 * Author:      Kama
 * Author url:  http://wp-kama.ru
 * Version:     4.3
 */

new Kama_Date_Human_Diff();

class Kama_Date_Human_Diff {

	public function __construct(){

		$is_new = version_compare( $GLOBALS['wp_version'], '5.3.0', '>=' );

		if( $is_new )
			add_filter( 'wp_date',   [ __CLASS__, 'difference' ], 11, 3 ); // WP 5.3
		else
			add_filter( 'date_i18n', [ __CLASS__, 'difference' ], 11, 3 ); // WP < 5.3

		//if( $is_new )
		//  add_filter( 'wp_date',   [ __CLASS__, 'month_declination' ], 11, 2 ); // WP 5.3
		//else
		//  add_filter( 'date_i18n', [ __CLASS__, 'month_declination' ], 11, 2 ); // WP < 5.3

		add_action( 'after_setup_theme', [ __CLASS__, 'fix_month_abbrev' ], 0 );
	}

	static function fix_month_abbrev(){
		global $wp_locale;

		$wp_locale->month_abbrev = [
			'Январь'    => 'янв.',
			'Февраль'   => 'фев.',
			'Март'      => 'мар.',
			'Апрель'    => 'апр.',
			'Май'       => 'май',
			'Июнь'      => 'июнь',
			'Июль'      => 'июль',
			'Август'    => 'авг.',
			'Сентябрь'  => 'сен.',
			'Октябрь'   => 'окт.',
			'Ноябрь'    => 'ноя.',
			'Декабрь'   => 'дек.',

		] + $wp_locale->month_abbrev;
	}

	/**
	 * Меняет выводимую дату на разницу: 3 часа назад, 5 дней назад, 7 месяцев назад...
	 * Функция для фильтра `wp_date`.
	 *
	 * Чтобы функция не работала, в формате нужно использовать обратный слэш \. Пр: "j F\ Y", "\дата: j F Y"
	 *
	 * @param string $date       Исходная дата получаемая из хука, её будем менять.
	 * @param string $req_format Формат даты который передается.
	 * @param int    $from_time  Метка времени от которой нужно считать разницу (в UNIX формате).
	 *
	 * @return string Дату в русском формате
	 */
	static function difference( $date, $req_format = '', $from_time = 0, $_to_time = null ){

		// не меняем в админке.
		// выходим, если в формате есть экранированные символы
		if( is_admin() || false !== strpos( $req_format, '\\' ) )
			return $date;

		$outpatt = _x( '%s назад', 'дней назад', 'km' );
		$cdaypatt = 'j n Y';

		// оптимизация
		static $to_time, $to_day;
		if( ! $to_time ) $to_time = $_to_time ?: time();         // $from_time прилетает с поправкой на GMT зону
		if( ! $to_day )  $to_day  = date( $cdaypatt, $to_time ); // день мес год без лидирующего нуля: 25 12 2015

		// в формате есть время и прошло меньше 24 часов
		if( preg_match( '/[aABgGhHis]/', $req_format ) ){

			$diff = $to_time - $from_time; // отдельно да!

			// отрицательно
			if( $diff < 0 )
				$outpatt = _x( 'через %s', 'через 2 дня', 'km' );

			$min_passed   = (int) abs( floor( $diff / 60 ) );
			$hours_passed = (int) abs( floor( $diff / 3600 ) );

			if( $min_passed === 0 )
				return '<span title="'. $date .'">'. __('сейчас','km') .'</span>';

			if( $min_passed < 60 )
				return '<span title="'. $date .'">'. sprintf( $outpatt, self::_plural( $min_passed, __('минута,минуты,минут','km') ) ) .'</span>';

			if( $hours_passed < 24 )
				return '<span title="'. $date .'">'. sprintf( $outpatt, self::_plural( $hours_passed, __('час,часа,часов','km') ) ) .'</span>';
		}

		// формате есть дата и прошло больше 24 часов
		if(
			( empty( $hours_passed ) || $hours_passed >= 24 )
			&&
			preg_match( '/[dDjLNSwz]/', $req_format )
		){

			$diff = $to_time - $from_time;
			$days_passed = (int) floor( $diff / DAY_IN_SECONDS );

			// если отрицательно
			if( $days_passed < 0 ){
				$days_passed *= -1;
				$outpatt = __( 'через %s', 'km' );
			}

			if( $days_passed === 0 || date( $cdaypatt, $from_time ) === $to_day )
				return __( 'сегодня', 'km' );

			if( $days_passed === 1 )
				return __( 'вчера', 'km' );

			// дни
			if( $days_passed < 30 ){
				$out = self::_plural( $days_passed, __( 'день,дня,дней', 'km' ) );
			}
			// месяцы
			elseif( $days_passed < 365 ){

				$months_passed = (int) floor( $days_passed / 30.5 ) ?: 1;
				$outpatt       = "<span title=\"$date\">$outpatt</span>";
				$is_short_patt = '/(?<!\\\\)[DM]/'; // в формате есть D или M (короткий день недели или месяц)

				if( preg_match( $is_short_patt, $req_format ) ){
					$out = $months_passed === 1 ? __( 'месяц', 'km' ) : "$months_passed " . _x( 'мес', 'месяц', 'km' );
				}
				else{
					$out = $months_passed === 1 ? __( 'месяц', 'km' ) : self::_plural( $months_passed, __( 'месяц,месяца,месяцев', 'km' ) );
				}
			}
			// годы
			elseif( $days_passed >= 365 ){

				$years_passed = (int) floor( $days_passed / ( 30.5 * 12 ) ) ?: 1; // лет прошло
				$months_passed = (int) floor( ( $days_passed - ( $years_passed * 365 ) ) / 30.5 ) ?: 1; // месяцев прошло
				$decimal = (int) ( round( $months_passed / 12, 2 ) * 10 ); // десятая часть месяца
				$decimal = $decimal ? ".$decimal" : '';

				$outpatt = "<span title=\"$date\">$outpatt</span>";

				// без числа вначале
				if( $years_passed === 1 && ! $decimal ){
					$out = __( 'год', 'km' );
				}
				// десятичное значение
				elseif( $years_passed === 1 && $decimal ){
					$out = $years_passed . "$decimal " . _x( 'года', '1.5 года назад', 'km' );
				}
				// целое число лет
				else{
					$out = $years_passed . "$decimal " . self::_plural( $years_passed, __( 'год,года,лет', 'km' ), true );
				}
			}

			return sprintf( $outpatt, $out );
		}

		return $date;
	}

	/**
	 * Русифицирует месяца и недели в дате.
	 * Функция для фильтра `wp_date`.
	 *
	 * @param string $date       Дата в принятом формате.
	 * @param string $req_format Формат передаваемой даты.
	 *
	 * @return string Дату в русском формате.
	 */
	static function month_declination( $date, $req_format ){

		// Выходим, если в формате нет "строковых" неделя или месяц
		if(
			! preg_match( '/[FMlS]/', $req_format )
			|| determine_locale() !== 'ru_RU'
			//|| false !== strpos( $req_format, '\\')
		)
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
	 * @param int          $number
	 * @param string|array $titles
	 * @param bool         $strip_num
	 *
	 * @return string
	 */
	static function _plural( $number, $titles, $strip_num = false ){

		is_string( $titles ) && $titles = array_map( 'trim', explode( ',', $titles ) );

		$titles_num = ( $number % 100 > 4 && $number % 100 < 20 ) // 5-19, 105-119, ...
			? 2
			: [ 2, 0, 1, 1, 1, 2 ][ min( $number % 10, 5 ) ];

		return ( $strip_num ? '' : "$number " ) . $titles[ $titles_num ];
	}

}
