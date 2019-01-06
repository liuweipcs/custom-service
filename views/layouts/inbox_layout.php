<?php 
use app\assets\AppAsset;
AppAsset::register($this);
?>
<?php $this->beginPage();?>
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
		<link rel="icon" href="../img/favicon.ico" />
		<?php $this->head() ?>
    
        <!-- MetisMenu CSS -->
        <!-- <link href="<?php echo yii\helpers\Url::base(true);?>/css/metisMenu.min.css" rel="stylesheet"> -->
    
        <!-- Custom CSS -->
        <link href="<?php echo yii\helpers\Url::base(true);?>/css/styles.css" rel="stylesheet">
    
        <!-- Morris Charts CSS -->
        <!-- <link href="<?php echo yii\helpers\Url::base(true);?>/css/morris.css" rel="stylesheet"> -->
    
        <!-- Custom Fonts -->
        <link href="<?php echo yii\helpers\Url::base(true);?>/css/font-awesome.min.css" rel="stylesheet" type="text/css">
        <!-- Bootstrap Table CSS -->
        <!-- <link href="<?php echo yii\helpers\Url::base(true);?>/css/bootstrap-table.css" rel="stylesheet"> -->	
	    <!-- jQuery -->
        <script src="<?php echo yii\helpers\Url::base(true);?>/js/jquery-1.9.1.min.js"></script>
	</head>

	<body>
<?php $this->beginBody();?>
<?php echo $this->render('header');?>
<?php echo $content;?>
<?php echo $this->render('footer');?>
<?php $this->endPage();?>