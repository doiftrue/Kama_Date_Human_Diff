$func = static function( $from_time  ){
    return sprintf( "\t%s\n", Kama_Date_Human_Diff::human_diff( $from_time, time(), true ) );
};

$func_day = static function( $from_time ){
    return sprintf( "\t%s\n", Kama_Date_Human_Diff::human_diff( $from_time, time(), false ) );
};

if( 10 ){
    
	echo "\nFuture (up to day):\n";
	echo $func( time() + 30 );
	echo $func( time() + 95 );
	echo $func( time() + ( 10 + 3600 ) );
	echo $func( time() + 24 * 3600 );
	echo $func( time() + 25 * 3600 );
	echo "\nPast (up to day):\n";
	echo $func( time() - 30 );
	echo $func( time() - 95 );
	echo $func( time() - 10 * 60 );
	echo $func( time() - ( 10 + 3600 ) );
	echo $func( time() - 24 * 3600 );
	echo $func( time() - 25 * 3600 );


	echo "\nFuture (any):\n";
	echo $func_day( time() + 23605 );
	echo $func_day( time() + 83605 );
	echo $func_day( time() + 893605 );
	echo $func_day( time() + 9893605 );
	echo $func_day( time() + 99893605 );
	echo "\nPast (any):\n";
	echo $func_day( time() - 3605 );
	echo $func_day( time() - 83605 );
	echo $func_day( time() - 113605 );
	echo $func_day( time() - 1113605 );
	echo $func_day( time() - 11113605 );
	echo $func_day( time() - 111113605 );
	echo $func_day( time() - 1111113605 );
	
}
