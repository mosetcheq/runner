<!DOCTYPE html>
<html lang="cz">
<head>
<meta charset="utf-8">
<title>Runtime errors</title>
<style>
body {
	margin: 0px;
	padding: 0px;
	font-family: 'Segoe UI', helvetica, sans-serif;
	font-size: 1em;
	font-weight: normal;
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
table {
	width: 100%;
	border-collapse: collapse;
}
th, td {
	text-align: left;
	padding: 4px;
}
tr:nth-of-type(odd) {
	background: #EEEEEE;
}
tr:nth-of-type(even) {
	background: #DDDDDD;
}

</style>
</head>
<body>
<header>
<h1>Runtime errors</h1>
</header>
<table>
 <thead>
  <tr>
   <th width="10%">Type</th>
   <th width="40%">Error</th>
   <th width="40%">File</th>
   <th width="10%">Line</th>
  </tr>
 </thead>
 <tbody>
<?php if($errors) foreach($errors as $error) { ?>
  <tr>
   <td><?=self::$E_TYPE[$error[0]];?></td>
   <td><?=$error[1];?></td>
   <td><?=$error[2];?></td>
   <td><?=$error[3];?></td>
  </tr>
<?php } ?>
 </tbody>
</table>
</body>
</html>
