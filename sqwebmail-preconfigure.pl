#! /usr/bin/perl

# Preconfigures squirrelmail for new email addresses; makes it use
# the correct From: field data.

# Accepts input from three directions:
# Specified as an argument, in the form:
# sqwebmail-preconfigure fullname email
# If 'fullname' contains a space it must be in quote makrs.

# You can pass a filename as the first argument, or pipe in some data,
# each must be in the same format, which is how they appear to record it,
# or at least the layout that they feel makes the most sense for sending. 
# Here's a sample, obviously the real file shouldn't have hashes at the 
# beginning of each line:
# (the blank lines are not mandatory, and neither is the capitalisation).

#1. Domain: www.londonbtxcentre.co.uk
#
#Email: info@
#Full Name: Info Londonbtxcentre
#
#Email: dr.mhmarion@
#Full Name: Dr. Marie-Helene Marion
#
#2. Domain: www.anglehouseorthodontics.co.uk
#
#Email: bhavnita@
#Full Name: Bhavnita
#
#Email: carlene@
#Full Name: Carlene

# The latest version of this is at 
# http://github.com/BigRedS/play/raw/master/sqwebmail-preconfigure.pl


if ( (!$ARGV[1]) && (! (-f $ARGV[0])) && (-t STDIN) ) {
		print "Usage:\n\t$0 \"[FULL-NAME]\" [EMAIL-ADDRESS]\n";
		print "\t$0 [FILE]\nwhere FILE is a file containing a list of the form\n";
		print "full name, email-address\nalternatively, pipe a list like that in on stdin\n";
	exit 1;
}

if (( -f $ARGV[0] && !$ARGV[1]) || ( !-t STDIN )) {

	if ( !-t STDIN){
		open ($fh, "<&STDIN");
	}else{
		open ($fh, "<", $ARGV[0]);
	}
	my ($domain, $lhs, $name);
	while(<$fh>){
		if (/Domain: (.+)+\n/i){
			$domain = $1;
			$domain =~ s/^www\.//;
		}
		if (/^Email:\s(\w+)\@?\n/i){
			$lhs = $1;
		}
		if (/^Full Name: (.+)\n/i){
			$name = $1;
			$email = $lhs."@".$domain;
			&preconfigure($name, $email);
		}
	}
}else{
	&preconfigure($ARGV[0], $ARGV[1]);
}


sub preconfigure(){
	my $name = shift;
	my $email = shift;
	my $filename;

	## Limitation: for an email address lhs@sub.domain.tld, this makes a file called lhs.sub.pref not lhs.sub.domain.pref
	## I don't know if this is correct.
	if ($email =~ /(.+\@[^\.]+)\.\w+/){
		 $filename = $1;
	}else{
		print "Couldn't deduce filename from email address ($email); you'll have to do this one manually, or fix the script\n";
		exit 1;
	}

	$filename =~ s/\@/\./;
	$filename .= ".pref";

	print "Configuring $email ($name) in $filename\n";

	`echo -e \"full_name=$name\" >> /var/lib/squirrelmail/data/$filename`;
	`echo -e \"email_address=$email\" >> /var/lib/squirrelmail/data/$filename`;
	`chown www-data:www-data /var/lib/squirrelmail/data/$filename`;
}
