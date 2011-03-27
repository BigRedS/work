#! /bin/bash
echo <<EOF
This script is utterly untested. It's only here because I thought the sequence of commands I ran
should really be in a script, rather than just a series of commands on wasted. Also, this way 
next time someone wants to import a bunch of Bind zonefiles into Sassy they've a reasonable
starting point for a script :)

zone2sql is a tool that ships with PowerDNS for migrating from Bind configs to SQL-backed PDNS 
ones.

It expects the domain to already be in pdns, but for it to have no records - it doesn't do 
anything with current records. 

EOF
DIR=$1
cd $DIR
echo "Here's a list of domains to put into Sassy:"
ls -m -w 1000000000 | sed -e s/\.txt,/,/ 
echo "What is the starting id of those domains? Just hit enter with no input to exit"
echo "and give yourself the chance to go and find the id"
read $START_ID
if [ "z$START_ID" == "z" ]
then
	exit 1
fi
echo "creating a zonefile..."
for i in `ls` do zone=$(echo $i | sed -e s/\.txt//) echo -e "zone \"$zone\" {\n\ttype master;\n\tfile \"./$i\";\n};"; done > named.conf
echo "capitalising record types..."
perl -pi -e 's/in\s*(txt|ns|a|mx|cname)/"in ".uc($1)/eg' *.txt
echo "setting NS records to dns0.positive-internet.com..."
perl -p -e 's/(\S+\s+IN\s+NS\s+)\S+\S+/$1 dns0.positive-internet.com/' *.txt
echo "making you an import.sql"
zone2sql --mysql --named-conf=./named.conf --verbose --start-id=$START_ID > import.sql
echo "Done! Please check my work"
 

  
  


