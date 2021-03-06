<?php
/*
 * sitecheck.php - an aggregate website content check
 * avi@positive-internet.com 2011
 *
 *
 * This script goes through the configured urls and checks each returns a page 
 * containing the text we think it is supposed to.
 * If any don't it prints an error to the web client and sends an appropriate 
 * email. If all are fine, it tells this to the visiting client, too. 
 *
 * The idea is that Nagios performs a content check upon it, and therefore monitors
 * the whole server's worth of virtualhosts with one content-check
 *
 * Configuration is in a JSON file, configured in $configFile. The format is:
 *
 * [{
 *	"name":"Hornsey Park Surgery",	
 *	"url":"http:\/\/www.hornseyparksurgery.co.uk\/",	
 *	"text":"All staff, reception nurses and doctors",	
 *	"email":"sami@greenbury.co.uk",	
 * },
 * Where:
 *   name:  name by which the site is known. *must* be unique, since it is used as the 
 *          key for an associative array.
 *   url:   URL through which to retrieve the site
 *   text:  Text to check for in the source of the page
 *   email: Who to email if that text isn't there
 *
 * leave email blank to have no mail sent. It is read at the beginning of the script
 * and rewritten at the end; the script *must* be able to write to it. It is in there
 * that it stores the "this was broken last time, so I won't send another email" memory.
 *
 * The plan is to add more checks, but I haven't thought of any yet.
 *
 */

// avi@positive-internet.com 2011


// This is put at the bottom of the email as a "go here to check everything" link:
$myurl = "http://positest.chits.positive-dedicated.net/sitecheck.php";
// Title (and entire content) of webpage if nothing's broken:
$success = "<h1>Don't worry. Everything is A-OK</h1>";
// Title of webpage if things are broken:
$failure = "<h1>Things are broken!</h1>";
// File in which to keep track of broken sites:
$errorFile = "./sitecheck.log";
$configFile = "./sitecheck.conf.json";

/*
* Sites is an array of arrays of site information. They are saved in $configFile
* and re-written there after script execution to remember some details. 
* These arrays contain the following key/value pairs:
*
*  'name'  => A human-understandable name of the website
*  'url'   => The URL to the page to be checked
*  'text'  => Some text to search for in the page at the above URL.
*             If this text is not found, the website is deemed to be 
*             'down'.
*  'email' => A comma-separated list of emails to which alerts are to 
*             be sent.
*  'lasterror' => A timestamp of the last time an error occured, reset
*            to 0 upon success.
*/
$sites = json_decode(file_get_contents($configFile), true);

if(!is_array($sites))
	throw new Exception('Config file not set');

// Output Buffer so we can put any output into the error log file
ob_start();


/*
 * Loop through the above-defined sites and see if we can find the text string in their
 * source. If not, push it to the $badThings array
 */
$hadErrors = false;
foreach($sites as &$site){
	if(fopen($site['url'], "r")){
		$fh = fopen($site['url'], "r");
		$page = stream_get_contents($fh);
		if(!strpos($page, $site['text'])){
			$error = "Content check failed";
			$site['error'] = "Content check failed. Page doesn't contain string <tt>".$site['text']."</tt>";

			if ($site['lasterror'] < strtotime('15 minutes ago'))
				if ($site['email'] != ""){
					errorMail($site);
				}
				// Uncomment this to be a CLI thing
				echo errorTerminal($site);
				// or this to be a web thing
				echo errorWeb($site);

			$site['lasterror'] = time();
			$hadErrors = true;
		}else{
			// If it loaded OK, clear the error messages
			$site['lasterror'] = 0;
			$site[''] = '';
		}
	}else{
		if ($site['lasterror'] < strtotime('15 minutes ago'))
			errorMail($site);
		// Uncomment this to be a CLI thing
//		echo errorTerminal($site);
		// or this to be a web thing
		echo errorWeb($site);
		$site['error'] = "Site not loadable";
		$site['lasterror'] = time();
		$hadErrors = true;
	}
}

if($hadErrors == false)
	echo isset($_SERVER['REMOTE_ADDR']) ? "<h2>Don't worry. Everything is A-OK</h2>" : "Don't worry. Everything is A-OK";

// Save the current state of sites
$json = json_encode($sites);
$json = str_replace(array('{', '}', ',"',''), array("{\n","\n}",",\t\n\"",''), $json);
file_put_contents($configFile, $json);

print "\n\n";

file_put_contents($errorFile, ob_get_contents());
ob_end_flush();

/*
* errorWeb is passed a $site array (as defined at the top of the script) and returns an
* htmlified 'report' aimed at a browser
*/ 
function errorWeb($site){
		$return = "<h3>".$site['name']."</h3>";
		$return .= "<h3><a href='".$site['url']."'>".$site['url']."</a></h3>";
		$return .= "<b>Error</b> :".$site['error']."</p>";
		return $return;
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
	
	mail($to, $subject, $body, $headers);
}

function errorTerminal($site){
	return $site['name']." is broken\n";
}
