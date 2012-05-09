#!/usr/bin/php
<?php

require_once './lib/Predis.php';

// Configuration
$hostname = 'localhost';
$port = 6379;
$redis_expire_key = 'expirewords';
$redis_hash_key = 'keywords';

// Reading configuration options from command line
foreach(getopt('h:p:') as $key => $val)
{
	switch($key)
	{
		case 'h':
			$hostname = $val;
			break;
		case 'p':
			$port = intval($val);
			break;
	}
}

echo "Connecting to Redis on {$hostname}:{$port}...\n";
$redis = new Predis\Client(array(
	'host' => $hostname, 
	'port' => $port, 
));

while(true)
{
	try
	{
		$expireString = $redis->lpop($redis_expire_key);
		if(!$expireString)
		{
			// Nothing on the queue, so sleep and try later
			echo "Nothing to expire.  Sleeping...\n";
			sleep(1);
			continue;
		}

		// Get the details of the expiration command
		list($timePosted, $word) = explode('_', $expireString);

		// Check if the expiration time (24 hours) has passed
		if((($timePosted + 86400) - time()) > 0)
		{
			// Sleep until next expiration
			$timeToSleep = (($timePosted + 86400) - time());
			echo "Sleeping {$timeToSleep}...\n";
			sleep($timeToSleep);
		}

		// Expire the word
		$daysAgo = (time() - $timePosted) / 86400;
		echo "Decrementing {$word} by 1, which was posted {$daysAgo} days ago.\n";
		$redis->zincrby($redis_hash_key, -1, $word);
	}
	catch(Predis\CommunicationException $e)
	{
		echo 'Communication Exception: '.$e->getMessage()."\n";
		exit;
	}
	catch(Predis\PredisException $e)
	{
		echo "Exception: ".$e->getMessage()."\n";
		continue;
	}

}

?>
