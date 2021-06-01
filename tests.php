$func = static function( $format, $from ){
    return sprintf( "\t%s\n", Kama_Date_Human_Diff::human_diff( $format, $from ) );
};


$format = 'd m Y h';

echo "\nFuture:\n";
echo $func( $format, time() + 30 );
echo $func( $format, time() + 65 );
echo $func( $format, time() + ( 10 + 3600 ) );
echo $func( $format, time() + 24 * 3600 );
echo $func( $format, time() + 25 * 3600 );
echo "\nPast:\n";
echo $func( $format, time() - 30 );
echo $func( $format, time() - 65 );
echo $func( $format, time() - 10 * 60 );
echo $func( $format, time() - ( 10 + 3600 ) );
echo $func( $format, time() - 24 * 3600 );
echo $func( $format, time() - 25 * 3600 );


$format = 'd m Y';

echo "\nFuture:\n";
echo $func( $format, time() + 23605 );
echo $func( $format, time() + 83605 );
echo $func( $format, time() + 893605 );
echo $func( $format, time() + 9893605 );
echo $func( $format, time() + 99893605 );
echo "\nPast:\n";
echo $func( $format, time() - 3605 );
echo $func( $format, time() - 113605 );
echo $func( $format, time() - 1113605 );
echo $func( $format, time() - 11113605 );
echo $func( $format, time() - 111113605 );
echo $func( $format, time() - 1111113605 );
