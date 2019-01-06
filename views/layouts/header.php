<?php
    use app\modules\accounts\models\UserAccount;
    use kartik\select2\Select2;
?>
<?php $platformList = \app\modules\accounts\models\Platform::getPlatformAsArray(); ?>

<div id="wrapper">

    <!-- Navigation -->
    <nav class="navbar navbar-default navbar-static-top" role="navigation" style="margin-bottom: 0;">
        <div class="navbar-header" >
            <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
            <div class="logo-div col-md-2">
                <a href="<?php echo \yii\helpers\Url::home(); ?>"><img src="<?php echo \yii\helpers\Url::base(); ?>/img/logo.png" title="" /></a>
            </div>
            <div class="col-md-10" style="height:40px;">
                <?php
                echo $this->render('nav.php', ['menuList' => $this->params['menuList']]); ?>
            </div>

        </div>
        <!-- /.navbar-header -->
        <ul class="nav navbar-top-links navbar-left" style="float: left">
            <form id="search-form1" class="form-horizontal" action="/orders/order/list"
                  method="get" role="form" style="margin-left: 10px">
                <li style="float:left;margin-left:5px;">
                    <div class="form-group">
                        <div class="col-lg-7" style="margin-top:10px;">
                            <?php
                            echo Select2::widget([
                                'id' => 'platform_codes',
                                'name' => 'platform_codes',
                                'data' => UserAccount::getLoginUserPlatformAccounts(),
                                'value' => \Yii::$app->request->getQueryParam('platform_codes'),
                                'options' => [
                                    'placeholder' => '--请输入--',
                                ],
                                'pluginOptions' => [
                                    'width' => '150px',
                                ],
                            ]);
                            ?>
                        </div>
                    </div>
                </li>
                <li style="float:left;margin-left:5px;">
                    <div class="form-group">
                        <div class="col-lg-7">
                            <select class="form-control" name="condition_option" style="margin-top:10px;width:110px;">
                                <option value="buyer_id"
                                    <?php if (\Yii::$app->request->getQueryParam('condition_option') == 'buyer_id') { ?>selected<?php } ?>>默认查询条件
                                </option>
                                <option value="item_id"
                                    <?php if (\Yii::$app->request->getQueryParam('condition_option') == 'item_id') { ?>selected<?php } ?>>ItemId
                                </option>
                                <option value="package_id"
                                    <?php if (\Yii::$app->request->getQueryParam('condition_option') == 'package_id') { ?>selected<?php } ?>>包裹号
                                </option>
                                <option value="paypal_id"
                                    <?php if (\Yii::$app->request->getQueryParam('condition_option') == 'paypal_id') { ?>selected<?php } ?>>paypal交易号
                                </option>
                                <option value="sku" <?php if (\Yii::$app->request->getQueryParam('condition_option') == 'sku') { ?>selected<?php } ?>>
                                    sku
                                </option>

                            </select>
                        </div>
                    </div>
                </li>
                <li style="float:left;margin-left:5px;">
                    <div class="form-group">
                        <div class="col-lg-7" style="margin-top:10px;">
                            <input type="text" class="form-control" name="condition_value" style="width:250px;" placeholder="客户id/邮箱/平台订单号/定位订单号"
                                   value="<?php echo \Yii::$app->request->getQueryParam('condition_value'); ?>">
                        </div>
                    </div>
                </li>
                <li style="float:left;margin-left:5px; margin-top:10px;" >
                    <button type="submit" class="btn btn-primary">搜索</button>
                </li>
            </form>
        </ul>
        <ul class="nav navbar-top-links navbar-right">

            <li style="margin-left:0"><span>欢迎，<?php echo $this->params['identity']->user_name; ?>！</span></li>
            <!-- /.dropdown -->
            <li class="dropdown">
                <a class="dropdown-toggle" data-toggle="dropdown" href="#">
                    <i class="fa fa-user fa-fw"></i> <i class="fa fa-caret-down"></i>
                </a>
                <ul class="dropdown-menu dropdown-user">
                    <li><a href="#"><i class="fa fa-user fa-fw"></i>用户信息</a>
                    </li>
                    <li><a href="#"><i class="fa fa-gear fa-fw"></i>修改密码</a>
                    </li>
                    <li class="divider"></li>
                    <li><a href="<?php echo \yii\helpers\Url::toRoute('/users/user/logout'); ?>"><i class="fa fa-sign-out fa-fw"></i>退出</a>
                    </li>
                </ul>
                <!-- /.dropdown-user -->
            </li>
            <!-- /.dropdown -->
        </ul>
        <!-- /.navbar-top-links -->
        <div class="navbar-default sidebar" role="navigation">
            <div class="sidebar-nav navbar-collapse">
            <?php if (isset($this->params['menuList']) && !empty($this->params['menuList'])) { ?>
                    <ul class="nav" id="side-menu">
                    <?php foreach ($this->params['menuList'] as $topMenu) { ?>
                            <li>
                                <a href="<?php echo $topMenu['route'] == '' ? 'javascript:void(0);' : $topMenu['route']; ?>">
                                    <i class="fa fa-fw <?php echo $topMenu['menu_icon']; ?>"></i><?php echo $topMenu['menu_name']; ?><span class="fa arrow"></span>
                                </a>
                                <?php if (isset($topMenu['children']) && !empty($topMenu['children'])) { ?>
                                    <ul class="nav nav-second-level">
                                    <?php foreach ($topMenu['children'] as $secondMenu) { ?>
                                            <li>
                                                <a href="<?php echo $secondMenu['route'] == '' ? 'javascript:void(0);' : $secondMenu['route']; ?>">
                                                    <i class="fa fa-fw <?php echo $secondMenu['menu_icon']; ?>"></i><?php echo $secondMenu['menu_name']; ?>
                                                <?php
                                                if (!empty($secondMenu['children'])) {
                                                    echo '<span class="fa arrow"></span>';
                                                }
                                                ?>
                                                </a>
                                                <?php if (isset($secondMenu['children']) && !empty($secondMenu['children'])) { ?>
                                                    <ul class="nav nav-third-level">
                                                        <?php foreach ($secondMenu['children'] as $thirdMenu) { ?>
                                                            <li>
                                                                <a href="<?php echo $thirdMenu['route'] == '' ? 'javascript:void(0);' : $thirdMenu['route']; ?>">
                                                                    <i class="fa fa-fw <?php echo $thirdMenu['menu_icon']; ?>"></i><?php echo $thirdMenu['menu_name']; ?>
                                                                </a>
                                                            </li>
                                                        <?php } ?>
                                                    </ul>
                                                <?php } ?>
                                            </li>
                                        <?php } ?>    
                                    </ul>
                                <?php } ?>
                            </li>
                        <?php } ?>
                    </ul>                   
                <?php } ?>
            </div>
        </div>
        <!-- /.navbar-static-side -->
    </nav>