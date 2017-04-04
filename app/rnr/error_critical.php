<!DOCTYPE html>
<html lang="cz">
<head>
<meta charset="utf-8">
<title><?=$err_str;?> @ <?=$err_file;?></title>
<style>
body {
	margin: 0px;
	padding: 0px;
	font-family: 'Segoe UI', helvetica, sans-serif;
	font-size: 1em;
	font-weight: normal;
	}
dl {
	font-family: courier;
	border: 16px solid #CCCCCC;
	color: #444444;
	}
dt {
	float: left;
	text-align: right;
	width: 48px;
	background: #CCCCCC;
	padding: 0px 2px;
	}
dd {
	display: block;
	margin: 0px;
	padding: 0px 2px;
	white-space: pre;
	}
.err {
	font-weight: bold;
	color: #000000;
	}
dd.err {
	background: #FFFFAA;
	}
header {
	background: #CC0000;
	color: #FFFFFF;
	padding: 16px;
	}
h1 {
	margin: 0px;
	font-weight: normal;
	font-size: 1.75em;
	}
.var {
	color: #0000CC;
	}
.str {
	color: #00AA00;
	}
.keyw {
	color: #AA0000;
	font-weight: bold;
}
.type {
	color: #FF00CC;
	font-style: italic;
}
</style>
</head>
<body>
<header>
<h1><?=nl2br(ucfirst($err_str));?></h1>
<?php if($err_file) { ?>
<p>Script <strong><?=$err_file?></strong> at line <strong><?=$err_line;?></strong></p>
<?php } ?>
<p><strong>DT:</strong><?=date('Y-m-d H:i:s', time());?> <strong>IP:</strong><?=$_SERVER['REMOTE_ADDR'];?> <strong>METHOD:</strong><?=$_SERVER['REQUEST_METHOD'];?> <strong>URI:</strong><?=$_SERVER['REQUEST_URI'];?> <?=($_SERVER['HTTP_REFERER'] ? ' <strong>Referer:</strong>'.$_SERVER['HTTP_REFERER']:'');?></p>
</header>
<?php if($err_file) { ?>
<dl>
<?php
if($start_add) echo("<dt>&nbsp;<dt><dd>...</dd>");
for($line = $start; $line <= $end; $line++) { ?>
	<dt<?=($eline == $line ? ' class="err"' : '');?>><?=$line+1;?></dt>
	<dd<?=($eline == $line ? ' class="err"' : '');?>><?=$source[$line];?>&nbsp;</dd>
<?php
}
if($end_add) echo("<dt>...<dt><dd>&nbsp;</dd>");
?>
</dl>
<?php } ?>
</body>
</html>
