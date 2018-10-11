<!DOCTYPE html>
<html>
<head>
 <base href="<?=Base;?>" />
 <meta charset="utf-8" />
 <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
 <meta name="viewport" content="width=device-width" />

 <title><?=$view->pagetitle;?></title>
 <meta name="description" content="<?=$view->description;?>" />
 <meta name="keywords" content="<?=$view->keywords;?>" />
</head>
<body>

<h1>Welcome to Runner framework Sandbox!</h1>
<p><?=$view->paragraph;?></p>

<h2>Submit form ...</h2>
<p>Submiting form calls method &quot;onMyformSubmit&quot; ...</p>
<form method="post">
  <?=Rnr\FormHandler::Sender('myform');?>
  <input type="text" name="name" placeholder="place name here">
  <input type="submit" value="submit">
</form>

</body>
</html>
