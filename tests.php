
$func = static function( $from_time ){
    return sprintf( "\t%s\n", Kama_Date_Human_Diff::human_diff( $from_time, time(), true ) );
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
	echo $func( time() + 23605 );
	echo $func( time() + 83605 );
	echo $func( time() + 893605 );
	echo $func( time() + 9893605 );
	echo $func( time() + 99893605 );
	echo "\nPast (any):\n";
	echo $func( time() - 3605 );
	echo $func( time() - 113605 );
	echo $func( time() - 1113605 );
	echo $func( time() - 11113605 );
	echo $func( time() - 111113605 );
	echo $func( time() - 1111113605 );
	
}
