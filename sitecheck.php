<?php
/*
* This script goes through its $sites array, and checks each site contains the 
* text we think it is supposed to. If any don't it prints an error to the web
* client and sends an appropriate email. If all are fine, it tells this to the 
* visiting client, too. 
* The idea is that Nagios performs a content check upon it, and therefore monitors
* the whole server's worth of virtualhosts with one content-check
*/

// avi@positive-internet.com 2011

error_reporting(0);

/*
* Sites is an array of arrays of site information.These arrays 
* contain the folliowing key/value pairs:
*
*  'name'  => A human-understandable name of the website
*  'url'   => The URL to the page to be checked
*  'text'  => Some text to search for in the page at the above URL.
*             If this text is not found, the website is deemed to be 
*             'down'.
*  'email' => A comma-separated list of emails to which alerts are to 
*             be sent. 
*/

$sites = array(
	array(			
		'name' => "Hornsey Park Surgery",
		'url' => "http://www.hornseyparksurgery.co.uk/",
		'text' => "All staff, reception nurses and doctors",
		'email' => "avi@positive-internet.com"
	),
	array(
		'name' => "Avi",
		'url'  => "http://avi.co/",
		'text' => "Avi",
		'email'=> "a@b.c"
	)
);

// This is put at the bottom of the email as a "go here to check everything" link:
$myurl = "http://positest.chits.positive-dedicated.net/sitecheck.php";
// Title (and entire content) of webpage if nothing's broken:
$success = "<h1>Don't worry. Everything is A-OK</h1>";
// Title of webpage if things are broken:
$failure = "<h1>Things are broken!</h1>";
// File in which to keep track of broken sites:
$errorFile = "./sitecheck.log";

print `echo "" > $errorFile`;

$knownErrors = array("Hornsey Park Surgery","");

/*
* Loop through the above-defined sites and see if we can find the text string in their
* source. If not, push it to the $badThings array
*/
$badThings = array();
`echo ""  > $errorFile`;
foreach($sites as $site){
	if(fopen($site['url'], "r")){
		$fh = fopen($site['url'], "r");
		$page = stream_get_contents($fh);
		if(!strpos($page, $site['text'])){
			$error = "Content check failed";
			$site['error'] = "Content check failed. Page doesn't contain string <tt>".$site['text']."</tt>";
			array_push($badThings, $site);
		}
	}else{
		$site['error'] = "Site not loadable";
		array_push($badThings, $site);
	}
}



/*
* If there's anything in $badThings, loop through it and print some errors. If not, 
* say that everything's alright.
*/

if ($badThings[0]){
#	print "$failure";
	foreach($badThings as $site){
		print "> ";
		// Only send an email if we didn't see this error on last run
		if (!in_array($site['name'], $knownErrors)){
			$fileString = $site['name'];
			if( `echo "$fileString" >> $errorFile`){
				print "<!-- :) -->";
			}else{
				print "<!-- :( -->";
			}
		}
		errorTerminal($site);
#		errorWeb($site);
#		errorMaiil($site);
	}
	print "\n";
}else{
	print "$success";
}



/*
* errorWeb is passed a $site array (as defined at the top of the script) and prints an
* htmlified 'report' aimed at a browser
*/ 
function errorWeb($site){
		print "<h3>".$site['name']."</h3>";
		print "<h3><a href=\"".$site['url']."\">".$site['url']."</a></h3>";
		print "\t<b>Error</b> :".$site['error']."</p>";
}



/*
* errorMail is passed a $site array (as defined at the top of the script) and sends an 
* email to the contents of $site['email'] with the report. Currently this is an attempted
* direct copy of the Montastic reports
*/
function errorMail($site){
	$to = $site['email'];
	$subject = "Alert: ".$site['url'];;
	$date = date("Y-m-d h:m O");
	$headers = "from: sitecheck@positive-internet.com";
	
	$body = "Website status: alert\n";
	$body.= "Date: $date\n";
	$body.= "Name: ".$site['name']."\n";
	$body.= "URL:  ".$site['url']."\n";
	$body.= "Error: ".$site['error']."\n\t";
	$body.= $site['text']."\n";
	$body.= "All sites' status at $myurl\n";
	
#	mail($to, $subject, $body, $headers);
}

function errorTerminal($site){
	print $site['name']." is broken\n";
}

print "\n\n";

