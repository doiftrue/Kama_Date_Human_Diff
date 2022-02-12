<?php

namespace Kama;

/**
 * Показывает разницу от текущей даты: 3 часа назад, 5 дней назад, 2 часа назад.
 * Руссифицирует даты в WordPress, переводит месяца и дни недели.
 *
 * @repo      https://github.com/doiftrue/Kama_Date_Human_Diff/blob/master/kama-date-human-diff.php
 * @changelog https://github.com/doiftrue/Kama_Date_Human_Diff/blob/master/changelog.md
 * @author    Kama (wp-kama.ru)
 *
 * @version   5.4
 */
abstract class WP_Date_Human_Diff {

	public static function init(){

		// WP 5.3
		if( version_compare( $GLOBALS['wp_version'], '5.3.0', '>=' ) ){
			add_filter( 'wp_date', [ __CLASS__, 'wp_hook_human_diff' ], 11, 3 );
		}
		// WP < 5.3
		else{
			add_filter( 'date_i18n', [ __CLASS__, 'wp_hook_human_diff' ], 11, 3 );
		}

		add_action( 'after_setup_theme', [ __CLASS__, 'fix_month_abbrev' ], 0 );

		//if( $is_new )
		//  add_filter( 'wp_date',   [ __CLASS__, 'month_declination' ], 11, 2 ); // WP 5.3
		//else
		//  add_filter( 'date_i18n', [ __CLASS__, 'month_declination' ], 11, 2 ); // WP < 5.3
	}

	/**
	 * Retrives translations strings.
	 */
	private static function l10n_strings(){

		return (object) [
			'future'      => _x( 'через %s', 'через 2 дня', 'km' ),
			'the_past'    => _x( '%s назад', 'дней назад', 'km' ),
			'today'       => __( 'сегодня', 'km' ),
			'yesterday'   => __( 'вчера', 'km' ),
			'tomorrow'    => __( 'завтра', 'km' ),
			'dec_year'    => _x( 'года', '1.5 года назад', 'km' ),
			'_sec'        => __( 'секунда,секунды,секунд', 'km' ),
			'_min'        => __( 'минута,минуты,минут', 'km' ),
			'_hour'       => __( 'час,часа,часов', 'km' ),
			'_day'        => __( 'день,дня,дней', 'km' ),
			'_month'      => __( 'месяц,месяца,месяцев', 'km' ),
			'_year'       => __( 'год,года,лет', 'km' ),
		];
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
	public static function wp_hook_human_diff( $date, $req_format = '', $from_time = 0 ){

		// не меняем в админке.
		// выходим, если в формате есть экранированные символы
		if( is_admin() || false !== strpos( $req_format, '\\' ) )
			return $date;

		// format has time
		if( preg_match( '/[aABgGhHis]/', $req_format ) ){
			$sec_min_hour = true;
		}
		// format has date
		elseif( preg_match( '/[dDjLNSwz]/', $req_format ) ){
			$sec_min_hour = false;
		}
		// special format - dont touch
		else {
			return $date;
		}

		return '<span title="'. $date .'">'. self::human_diff( $from_time, $sec_min_hour ) .'</span>';
	}

	/**
	 * Calculate difference between two specified timestamps, and convert the difference
	 * into human readable format. Ex: `10 minutes ago`
	 *
	 * @param int  $from_time
	 * @param bool $sec_min_hour Show seconds/minutes/hours or start from days only.
	 * @param int  $to_time
	 *
	 * @return string
	 */
	public static function human_diff( $from_time = 0, $sec_min_hour = true, $to_time = 0 ){

		// optimization
		static $l10n;
		$l10n || $l10n = self::l10n_strings();

		$to_time || $to_time = time();

		$diff = $to_time - $from_time;
		$is_future = $diff < 0;
		$sec_passed = abs( $diff );

		$outpatt = $is_future ? $l10n->future : $l10n->the_past;

		// less then 24 hours
		if( $sec_min_hour ){

			$min_passed   = (int) floor( $sec_passed / 60 );
			$hours_passed = (int) floor( $sec_passed / 3600 );

			if( $sec_passed < 60 )
				return sprintf( $outpatt, self::_plural( $sec_passed, $l10n->_sec ) );

			if( $min_passed < 60 )
				return sprintf( $outpatt, self::_plural( $min_passed, $l10n->_min ) );

			if( $hours_passed <= 24 )
				return sprintf( $outpatt, self::_plural( $hours_passed, $l10n->_hour ) );
		}

		$days_passed = (int) floor( $sec_passed / DAY_IN_SECONDS );

		// today
		if( date( 'j n Y', $from_time ) === date( 'j n Y', $to_time ) ){
			return $l10n->today;
		}

		// yesterday
		if( $days_passed < 1 ){
			return $is_future ? $l10n->tomorrow : $l10n->yesterday;
		}

		// days
		if( $days_passed < 30 ){
			$out = self::_plural( $days_passed, $l10n->_day );

			return sprintf( $outpatt, $out );
		}

		// months
		if( $days_passed < 365 ){

			$months_passed = (int) floor( $days_passed / 30.5 ) ?: 1;

			$out = self::_plural( $months_passed, $l10n->_month, ( $months_passed === 1 ) );

			return sprintf( $outpatt, $out );
		}

		// years
		$years_passed = (int) floor( $days_passed / 365 ) ?: 1;
		$rest_months_passed = (int) floor( ( $days_passed % 365 ) / 30.5 ) ?: 1;
		$decimal = (int) ( round( $rest_months_passed / 12, 2 ) * 10 ); // десятая часть месяца

		// one year with decimal (float value)
		if( $years_passed === 1 && $decimal ){
			$out = "$years_passed.$decimal $l10n->dec_year";
		}
		// no number at the beginning
		elseif( $years_passed === 1 && ! $decimal ){
			$out = self::_plural( $years_passed, $l10n->_year, true );
		}
		// many years with/without decimal
		else{
			$decimal_str = $decimal ? ".$decimal" : '';
			$out = $years_passed . "$decimal_str " . self::_plural( $decimal ?: $years_passed, $l10n->_year, true );
		}

		return sprintf( $outpatt, $out );

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
		){
			return $date;
		}

		return strtr( $date, [
			'Январь'=>'января', 'Февраль'=>'февраля', 'Март'=>'марта', 'Апрель'=>'апреля', 'Май'=>'мая', 'Июнь'=>'июня', 'Июль'=>'июля', 'Август'=>'августа', 'Сентябрь'=>'сентября', 'Октябрь'=>'октября', 'Ноябрь'=>'ноября', 'Декабрь'=>'декабря',

			'Янв'=>'янв.', 'Фев'=>'фев.', 'Мар'=>'март', 'Апр'=>'апр.', 'Июн'=>'июнь', 'Июл'=>'июль', 'Авг'=>'авг.', 'Сен'=>'сен.', 'Окт'=>'окт.', 'Ноя'=>'ноя.', 'Дек'=>'дек.',

			'January'=>'января', 'February'=>'февраля', 'March'=>'марта', 'April'=>'апреля', 'May'=>'мая', 'June'=>'июня', 'July'=>'июля', 'August'=>'августа', 'September'=>'сентября', 'October'=>'октября', 'November'=>'ноября', 'December'=>'декабря',

			'Jan'=>'янв.', 'Feb'=>'фев.', 'Mar'=>'март.', 'Apr'=>'апр.', 'Jun'=>'июня', 'Jul'=>'июля', 'Aug'=>'авг.', 'Sep'=>'сен.', 'Oct'=>'окт.', 'Nov'=>'нояб.', 'Dec'=>'дек.',

			'Sunday'=>'воскресенье', 'Monday'=>'понедельник', 'Tuesday'=>'вторник', 'Wednesday'=>'среда', 'Thursday'=>'четверг', 'Friday'=>'пятница', 'Saturday'=>'суббота',

			'Sun'=>'вос.', 'Mon'=>'пон.', 'Tue'=>'вт.', 'Wed'=>'ср.', 'Thu'=>'чет.', 'Fri'=>'пят.', 'Sat'=>'суб.', 'th'=>'', 'st'=>'', 'nd'=>'', 'rd'=>'',
		] );
	}


}


