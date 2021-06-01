<?php

new Kama_Date_Human_Diff();

/**
 * Показывает разницу от текущей даты: 3 часа назад, 5 дней назад, 2 часа назад.
 * Руссифицирует даты в WordPress, переводит месяца и дни недели.
 *
 * @repo      https://github.com/doiftrue/Kama_Date_Human_Diff/blob/master/kama-date-human-diff.php
 * @changelog https://github.com/doiftrue/Kama_Date_Human_Diff/blob/master/kama-date-human-diff.php
 * @author    Kama (wp-kama.ru)
 * @version   5.0
 */
class Kama_Date_Human_Diff {

	/** @var object */
	private static $l10n;

	public function __construct(){

		self::$l10n = (object) [
			'after'       => _x( 'через %s', 'через 2 дня', 'km' ),
			'before'      => _x( '%s назад', 'дней назад', 'km' ),
			'today'       => __( 'сегодня', 'km' ),
			'dec_year'    => _x( 'года', '1.5 года назад', 'km' ),
			'short_month' => _x( 'мес', 'месяц', 'km' ),
			'_sec'        => __( 'секунда,секунды,секунд', 'km' ),
			'_min'        => __( 'минута,минуты,минут', 'km' ),
			'_hour'       => __( 'час,часа,часов', 'km' ),
			'_day'        => __( 'день,дня,дней', 'km' ),
			'_month'      => __( 'месяц,месяца,месяцев', 'km' ),
			'_year'       => __( 'год,года,лет', 'km' ),
		];

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

	public static function fix_month_abbrev(){
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
	 * @param string $req_format Нобходимый формат даты.
	 * @param int    $from_time  Метка времени от которой нужно считать разницу (в UNIX формате).
	 *
	 * @return string Дату в русском формате
	 */
	public static function difference( $date, $req_format = '', $from_time = 0, $to_time = null ){

		// не меняем в админке.
		// выходим, если в формате есть экранированные символы
		if( is_admin() || false !== strpos( $req_format, '\\' ) )
			return $date;

		return '<span title="'. $date .'">'. self::human_diff( $req_format, $from_time, $to_time ) .'</span>';
	}

	/**
	 * @param string $req_format
	 * @param int    $from_time
	 * @param int    $to_time
	 *
	 * @return string
	 */
	public static function human_diff( $req_format = '', $from_time = 0, $to_time = 0 ){

		// $from_time прилетает с поправкой на GMT зону
		if( ! $to_time )
			$to_time = time();

		$diff = $to_time - $from_time;
		$is_negative = $diff < 0;
		$sec_passed = abs( $diff );

		$l10n = & self::$l10n;

		$outpatt = $is_negative ? $l10n->after : $l10n->before;

		// format has time and less then 24 hours passed
		if( preg_match( '/[aABgGhHis]/', $req_format ) ){

			$min_passed   = (int) floor( $sec_passed / 60 );
			$hours_passed = (int) floor( $sec_passed / 3600 );

			if( $sec_passed < 60 )
				return sprintf( $outpatt, self::_plural( $sec_passed, 'sec' ) );

			if( $min_passed < 60 )
				return sprintf( $outpatt, self::_plural( $min_passed, 'min' ) );

			if( $hours_passed <= 24 )
				return sprintf( $outpatt, self::_plural( $hours_passed, 'hour' ) );

			$hours_passed = false; // to go to date calculation
		}

		// format has date and more then 24 hours passed
		if( empty( $hours_passed ) && preg_match( '/[dDjLNSwz]/', $req_format ) ){

			$days_passed = (int) floor( $sec_passed / DAY_IN_SECONDS );

			$cdaypatt = 'j n Y'; // day month year without leading zero: 25 12 2015

			if( date( $cdaypatt, $from_time ) === date( $cdaypatt, $to_time ) )
				return $l10n->today;

			// days
			if( $days_passed < 30 ){
				$out = self::_plural( $days_passed, 'day' );

				return sprintf( $outpatt, $out );
			}

			// months
			if( $days_passed < 365 ){

				$months_passed = (int) floor( $days_passed / 30.5 ) ?: 1;

				// format has D or M (short name of weekday or month)
				if( preg_match( '/(?<!\\\\)[DM]/', $req_format ) ){
					$out = ( $months_passed === 1 )
						? self::_plural( 1, 'month', true )
						: "$months_passed $l10n->short_month";
				}
				else{
					$out = ( $months_passed === 1 )
						? self::_plural( 1, 'month', true )
						: self::_plural( $months_passed, 'month' );
				}

				return sprintf( $outpatt, $out );
			}

			// years
			if( $days_passed >= 365 ){

				$years_passed = (int) floor( $days_passed / ( 30.5 * 12 ) ) ?: 1; // лет прошло
				$months_passed = (int) floor( ( $days_passed - ( $years_passed * 365 ) ) / 30.5 ) ?: 1; // месяцев прошло
				$decimal = (int) ( round( $months_passed / 12, 2 ) * 10 ); // десятая часть месяца
				$decimal = $decimal ? ".$decimal" : '';

				// no number at the beginning
				if( $years_passed === 1 && ! $decimal ){
					$out = self::_plural( 1, 'year', true );
				}
				// decimal (float) value
				elseif( $years_passed === 1 && $decimal ){
					$out = $years_passed . "$decimal $l10n->dec_year";
				}
				// integer number of years
				else{
					$out = $years_passed . "$decimal " . self::_plural( $years_passed, 'year', true );
				}
			}

			return sprintf( $outpatt, $out );
		}

		return '';
	}

	/**
	 * Склонение. Возвращает переданное число и слово после него в нужном склонении.
	 *
	 * @param int    $number
	 * @param string $titles
	 * @param bool   $strip_num
	 *
	 * @return string
	 */
	private static function _plural( $number, $titles, $strip_num = false ){

		$titles = self::$l10n->{"_$titles"} ?? $titles;

		$titles = array_map( 'trim', explode( ',', $titles ) );

		$titles_num = ( $number % 100 > 4 && $number % 100 < 20 ) // 5-19, 105-119, ...
			? 2
			: [ 2, 0, 1, 1, 1, 2 ][ min( $number % 10, 5 ) ];

		return ( $strip_num ? '' : "$number " ) . $titles[ $titles_num ];
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
	public static function month_declination( $date, $req_format ){

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


}
