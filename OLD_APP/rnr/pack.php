<?php

$output = '<?php namespace Rnr {';
$modules = explode(',', 'db.php,error.php,errordocument.php,io.php,tools.php,router.php');
foreach($modules as $f) {
	$contents = php_strip_whitespace($f);
	$contents = str_replace('<?php', '', $contents);
	$contents = str_replace('namespace Rnr;', '', $contents);
//	$contents = str_replace(';', ";\n", $contents);
	$output.= ' '.trim($contents).' ';
}
$output.= '} ';
$contents = php_strip_whitespace('boot.php');
$contents = str_replace('<?php ', '', $contents);
$output.= $contents;
file_put_contents('runner.min.php', $output);
