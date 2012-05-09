#!/usr/bin/php
<?php

require_once './lib/Predis.php';

// Configuration
$hostname = 'localhost';
$port = 6379;
$redis_tweetqueue_key = 'tweetqueue';
$redis_expire_key = 'expirewords';
$redis_hash_key = 'keywords';
$stopWords = file('./stopwords.txt');
$stopWords = array_map('trim', $stopWords);


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
		$tweet = $redis->lpop($redis_tweetqueue_key);
		$tweetObj = json_decode($tweet);
		if(!$tweetObj)
		{
			// Nothing on the queue, so sleep and try later
			echo "Sleeping...\n";
			sleep(1);
			continue;
		}
		if(isset($tweetObj->text))
		{
			$text = $tweetObj->text;
	
			// Break down into words
			$words = preg_split('/[\s]+/', $text);

			$affectedWords = array();
	
			foreach($words as $word)
			{
				// Sanitize word
				$word = clean_text($word);
				$word = strtolower($word);
	
				// Only add word if longer than 2 chars
				if(strlen($word) > 2)
				{
					// Increase tally for word by 1
					$redis->zincrby($redis_hash_key, 1, $word);
					// Save expiration
					$redis->rpush($redis_expire_key, time().'_'.$word);
					$affectedWords[] = $word;
				}
			}

			echo 'Tweet words: '.implode(', ', $affectedWords)."\n";
		}
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

	// Debug:
	//sleep(1);
}

function clean_text($str)
{
	// Change accented characters to non-accented equivalent
	$str = normalize($str);
	$str = preg_replace('/[^a-z0-9]/i', '', $str);
	return $str;
}

function normalize ($string) {
	$table = array(
		'Š'=>'S', 'š'=>'s', 'Đ'=>'Dj', 'đ'=>'dj', 'Ž'=>'Z', 'ž'=>'z', 'Č'=>'C', 'č'=>'c', 'Ć'=>'C', 'ć'=>'c',
		'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E',
		'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O',
		'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U', 'Ú'=>'U', 'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss',
		'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a', 'æ'=>'a', 'ç'=>'c', 'è'=>'e', 'é'=>'e',
		'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o',
		'ô'=>'o', 'õ'=>'o', 'ö'=>'o', 'ø'=>'o', 'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'ý'=>'y', 'ý'=>'y', 'þ'=>'b',
		'ÿ'=>'y', 'Ŕ'=>'R', 'ŕ'=>'r',
    );
    
    return strtr($string, $table);
}

?>

