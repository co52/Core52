<?

require_once('nameparse.php');

$names		=	file('test-names.txt');

foreach($names as $name) {
	$name	=	trim($name);
	if(!$name) { continue; }

	$namea	=	parse_name($name);
	if(count($namea) > 0) {
		echo "full:\t$name\n";
		print_name($namea);
		echo "\n";
		}
	}

function	print_name($namea) {
	$parts	=	array('title','first','middle','last','suffix');
	foreach($parts as $part) {
		if($namea[$part]) {
			echo "$part:\t'$namea[$part]'\n";
			}
		}
	echo "\n";
	}

?>
