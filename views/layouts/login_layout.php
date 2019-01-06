<?php echo $this->beginPage();?>
<!DOCTYPE html>
<html lang="zh-CN">

	<head>
    <title>易佰网络客服系统</title>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta http-equiv="X-UA-Compatible" content="IE=10">
		<meta http-equiv="X-UA-Compatible" content="IE=9">
		<meta http-equiv="X-UA-Compatible" content="IE=8">
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<meta name="renderer" content="webkit|ie-comp|ie-stand">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<?php echo $this->head();?>
		<link rel="icon" href="../img/favicon.ico" />
		<link type="text/css" rel="stylesheet" href="<?php echo yii\helpers\Url::base(true);?>/css/bootstrap.css" />
		<link type="text/css" rel="stylesheet" href="<?php echo yii\helpers\Url::base(true);?>/css/mtywl.css" />
		<link type="text/css" rel="stylesheet" href="<?php echo yii\helpers\Url::base(true);?>/css/mtywlico.css" />
		<link type=" text/css" rel="stylesheet" href="<?php echo yii\helpers\Url::base(true);?>/css/mtywlpage.css" />
		<link type=" text/css" rel="stylesheet" href="<?php echo yii\helpers\Url::base(true);?>/css/jquery.tagsinput.css" />
		<link type=" text/css" rel="stylesheet" href="<?php echo yii\helpers\Url::base(true);?>/css/combo.select.css" />
		<script type="text/javascript" language="JavaScript" src="<?php echo yii\helpers\Url::base(true);?>/js/jquery-1.9.1.min.js"></script>
		<script type="text/javascript" language="JavaScript" src="<?php echo yii\helpers\Url::base(true);?>/js/layer.js"></script>
		<script type="text/javascript" language="JavaScript" src="<?php echo yii\helpers\Url::base(true);?>/js/jquery.tagsinput.js"></script>
		<script type="text/javascript" language="JavaScript" src="<?php echo yii\helpers\Url::base(true);?>/js/jquery.combo.select.js"></script>
	</head>

	<body>
<?php echo $this->beginBody();?>	
<?php echo $this->render('header.phtml');?>
<?php echo $content;?>
<?php echo $this->render('footer.phtml');?>