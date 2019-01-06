<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">
    <title>易佰网络客服系统</title>
    <!-- Bootstrap Core CSS -->
    <link href="<?php echo yii\helpers\Url::base(true);?>/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="<?php echo yii\helpers\Url::base(true);?>/css/styles.css" rel="stylesheet">
    
    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
        <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
        <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->

</head>

<body>

    <div class="container">
        <div class="row">
            <div class="col-md-4 col-md-offset-4">
                <div class="login-panel panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title">欢迎使用易佰客服系统</h3>
                    </div>
                    <div class="panel-body">
                        <form role="form" action="<?php echo \yii\helpers\Url::toRoute('/users/user/login');?>" method="post">
                            <fieldset>
                                <div class="form-group">
                                    <input class="form-control" placeholder="登录名" name="login_name" type="text" autofocus>
                                </div>
                                <div class="form-group">
                                    <input class="form-control" placeholder="密码" name="login_password" type="password" value="">
                                </div>
                                <!--<div class="checkbox">
                                     <label>
                                        <input name="remember" type="checkbox" value="1">保持登录
                                    </label>
                                </div> -->
                                <div class="form-group">
                                    <span class="text-danger"><?php echo $errorMsg;?></span>
                                </div>
                                <!-- Change this to a button or input when using this as a form -->
                                <button type="submit" class="btn btn-lg btn-success btn-block">登录</button>
                            </fieldset>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery -->
    <script src="<?php echo yii\helpers\Url::base(true);?>/js/jquery-1.9.1.min.js"></script>

    <!-- Bootstrap Core JavaScript -->
    <script src="<?php echo yii\helpers\Url::base(true);?>/js/bootstrap.min.js"></script>

    <!-- Metis Menu Plugin JavaScript -->
    <script src="<?php echo yii\helpers\Url::base(true);?>/js/metisMenu.min.js"></script>
    
    <script src="<?php echo yii\helpers\Url::base(true);?>/js/layer/layer.js"></script>
    
    <!-- Custom Theme JavaScript -->
    <script src="<?php echo yii\helpers\Url::base(true);?>/js/system.js"></script>

</body>

</html>