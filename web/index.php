<?php
require_once './lib/Predis.php';

// Connect to Redis
$redis = new Predis\Client(array(
        'host' => 'localhost',
        'port' => 6379,
));

// Get the keywords
try
{
	$keywords = $redis->zrevrange('keywords', 0, 1000, 'withscores');
}
catch(Predis\CommunicationException $e)
{
	echo 'Communication Exception: '.$e->getMessage()."\n";
}
catch(Predis\PredisException $e)
{
	echo "Exception: ".$e->getMessage()."\n";
}

// Get stop words
$stopWords = file('stopwords.txt');
$stopWords = array_map('trim', $stopWords);
//$stopWords = array_map(function($str) {$str = trim($str); return clean_text($str);}, $stopWords);
//$stopWords = array_filter($stopWords, function($str) {return strlen($str) > 2;});
//die(implode("\n", $stopWords));

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
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Popular Keywords on Twitter</title>
<style type="text/css">
* {
	font-family: Helvetica, Arial, sans-serif;
}

img {
	border: 0;
}

body {
	margin: 0;
	padding: 0;
	background: #f2f2f2;
	text-align: center;
}

header {
	background: #1181A6;
	text-align: center;
	margin-bottom: 10px;
	height: 100px;
}

#keywords {
	text-align: center;
	margin: auto;
}

#keywords td {
	border-left: 1px solid #aaa;
	border-top: 1px solid #aaa;
	border-bottom: 2px solid #aaa;
	border-right: 2px solid #aaa;
	padding: 5px;
}

#keywords th {
	border-bottom: 2px solid #888;
}

footer {
	font-size: small;
	color: #aaa;
	margin: 10px 0 10px 0;
}
</style>
<script type="text/javascript" src="http://code.jquery.com/jquery-1.4.4.min.js"></script>
<script type="text/javascript">
var infoReload = function()
{
	$('#keywords').load('index.php?'+rnd()+' #keywords');
}
var rnd = function()
{
	return String((new Date()).getTime()).replace(/\D/gi,'');
}
setInterval(infoReload, 3000);
</script>
</head>
<body>
<header>
<a href="index.php">
<img src="images/twitterbird.png" style="width: 100px; vertical-align: middle" /><img src="images/tpklogo.png" style="vertical-align: middle" />
</a>
</header>
<table id="keywords">
	<tr><th>Keywords</th><th>Uses in last 24 hours</th></tr>
<?php foreach($keywords as $keywordInfo): ?>
	<?php if(!in_array($keywordInfo[0], $stopWords) && strlen($keywordInfo[0]) > 3): ?>
	<tr><td><?php echo $keywordInfo[0];?></td><td><?php echo $keywordInfo[1];?></td></tr>
	<?php endif;?>
<?php endforeach; ?>
</table>
<footer>
&copy; 2010 Dennis Tsang.
</footer>
</body>
</html>
