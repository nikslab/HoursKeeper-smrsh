#!/etc/smrsh/php
<?php
/**
 *
 * Script to process HoursKeeper App CSV exports and put them into MySQL database
 * IOS: https://itunes.apple.com/us/app/hours-keeper-time-tracking/id563155321?mt=8
 * Android:
 * 
 * HoursKeeper lets you export timesheet data into a CSV and mail it out.
 * Create a separate e-mail alias on your mail server for it, and pipe it to this script
 * using smrsh.
 *
 * @author  Nik Stankovic, 2015
 * 
 */

$pid = getmypid();
$upload_directory = "/mnt/Dropbox/Lab/HoursKeeper/smrsh";
$output_file = "$upload_directory/$pid.txt";
$sql_file = "$upload_directory/$pid.sql";

// Start parsing e-mail
$email = "";
$stdin = fopen( 'php://stdin', 'r' );
$line = "a";
// Read the e-mail line by line until you encounter Clinet Name (which is a field marker)
while (strpos($line, "Client Name") === false) {
    $line = trim(fgets(STDIN));
}

// Skip two additional lines
$line = fgets(STDIN);
$line = fgets(STDIN);

// E-mail contents should start here, import all lines excluding line(s) that contain -Apple-Mail
while (strpos($line, "-Apple-Mail") === false) {
    $email .= $line;
    $line = fgets(STDIN);
}

// Decode the e-mail attachment
$decoded = quoted_printable_decode($email);

// Save the contents into a local file as a backup (optional)
file_put_contents($output_file, $decoded);

//
$DATA = explode("\n", $decoded);

// Config
$DEBUG = 0;

// Connect to database
$mysqli = new mysqli( "localhost", "hrskeeper", "password", "HoursKeeper");

// Determine max(date) in database when we logged 24 hours, in a loop backwards until found
// We will start importing from there later
$found = 0;
while( !$found ) {
    
    // Get max(date)
    $select = "select max(date) as m from timesheet";
    $result = $mysqli->query( $select );
    $queryData = $result->fetch_array( MYSQL_ASSOC );
    $max_date = $queryData["m"];
    debug(4, "Max date is $max_date");
    
    if( $max_date != "" ) {
        
        // Get number of hours logged on max(date)
        $select = "select sum(worked) as s from timesheet where date = '" . $max_date . "'";
        $result = $mysqli->query($select);
        $queryData = $result->fetch_array( MYSQL_ASSOC );
        $worked = $queryData["s"];
        debug(4, "Hours worked on max(date) is $worked");
        
        // If number of hours on max(date) is less than 24 then delete all hours for max(date)
        if( $worked >= ( 24 * 60 ) ) { $found++; }
        else {
            $delete = "delete from timesheet where date = '" . $max_date . "'";
            $result = $mysqli->query($delete);
            debug(4, "Deleting max(date) data with $delete");
        }

    }
    else { $found++; }  // database is empty
    
}

// Now get in a loop, entry by entry and generate SQL statements
$c = 0;
$client = "a";
foreach( $DATA as $line ) {
    $parts = str_getcsv ( $line, ",", '"' );
    $c++;

    $client = $mysqli->real_escape_string( $parts[0] );

    // Date time clean-up
    $timeparts = explode( " ", $parts[1] );
    $timestamp = strtotime( $timeparts[0] . " " . $timeparts[1] . " " . $timeparts[2] );
    $date = date("Y-m-d", $timestamp);
    $pos = strpos( $parts[4], ":" );
    if( $pos === false ) { // new format 1h 30m
        $workedparts = explode( " ", $parts[4] );
        $hours = str_replace( "h", "", $workedparts[0] );
        $minutes = str_replace( "m", "", $workedparts[1] );
        $worked = $hours*60 + $minutes;
    }
    else { // old format 1:30h
        $workedparts = explode( ":", $parts[4] );
        $hours = $workedparts[0];
        $minutes = str_replace( "h", "", $workedparts[1] );
        $worked = $hours*60 + $minutes;
    }

    mb_internal_encoding("UTF-8");

    $rate = mb_substr( $parts[5], 1 );
    $rate = str_replace( "/h", "", $rate );
    $rate = str_replace( ",", "", $rate );

    $amount = mb_substr( $parts[6], 1 );
    $amount = str_replace( ",", "", $amount );

    $note = $mysqli->real_escape_string( $parts[7] );

    // SQL statement
    $insert = "INSERT INTO timesheet (client, date, worked, rate, amount, note ) VALUES ";
    $insert .= "('$client', '$date', $worked, $rate, $amount, '$note');\n";
    debug(5, "One entry insert: $insert");
    
    $transaction = ""; // holds all insert statements, we write them to file
    if( ( ( $date > $max_date) || ( $max_date == "" ) ) && ( $client != "" ) ) {
        $transaction .= $insert;
        $result = $mysqli->query( $insert ); // run the query anyway
    }
}

// Write transaction to file if you like as another form of backup (optional)
//file_put_contents($sql_file, $transaction);

?>