<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
?>
<style>
    .head-v3{position:relative;z-index:100;width: auto;height: 50px;}
    .head-v3 .navigation-inner{margin:0 auto;width:100%;position:relative}
    .navigation-up{height:50px;background:#27303F;overflow: hidden;}
    .navigation-up .navigation-v3{float:left;_margin-left:10px;height: 50px;overflow: hidden;}
    .navigation-up .navigation-v3 ul{float:left;cursor: pointer;height: 50px;overflow: hidden;}
    .navigation-up .navigation-v3 li{float:left;font:normal 16px/59px "microsoft yahei";color:#FFFFFF;width: auto;padding:0 5px;list-style-type:none;line-height: 50px;font-weight: bold;font-size: 14px;}
    .navigation-up .navigation-v3 .nav-up-selected{background:#344157}
    .navigation-up .navigation-v3 .nav-up-selected-inpage{background:#202833}
    .navigation-up .navigation-v3 li h2{font-weight:normal;padding:0;margin:0}
    .navigation-up .navigation-v3 li a{padding:0 25px;color:#fff;display:inline-block;height:50px;font-family:"microsoft yahei";}

    .navigation-down{position:absolute;top:51px;left:0px;width:100%}
    .navigation-down .nav-down-menu{width:auto;margin:0;background:#344157;position:absolute;top:0px;}
    .navigation-down .nav-down-menu .navigation-down-inner{margin:auto;width:1500px;position:relative}
    .navigation-down .nav-down-menu dl{float:left;margin:18px 80px 18px 0}
    .navigation-down .menu-1 dl{margin:20px 80px 25px 0;font-size:13px;}
    .navigation-down .menu-1 dt{font:normal 16px "microsoft yahei";color:#61789e;padding-bottom:10px;border-bottom:1px solid #61789e;margin-bottom:10px}
    .navigation-down .menu-1 dd a{color:#fff;font:normal 14px/30px "microsoft yahei;";font-size:13px;}
    .navigation-down .menu-1 dd a:hover{color:#60aff6}
    .navigation-down .menu-2 dd a,.navigation-down .menu-3 dd a{color:#fff;font:normal 16px "microsoft yahei";font-size:12px;}
    .navigation-down-inner dl dd{width: 32%;float: left;text-align: left; margin-left: 10px;}
    .nav-down-menu{border-radius:5px;}
    ul li{list-style: none;}
</style>
<script type="text/javascript">
    jQuery(document).ready(function () {
        var qcloud = {};
        $('[_t_nav]').hover(function () {
            var _nav = $(this).attr('_t_nav');
            clearTimeout(qcloud[ _nav + '_timer' ]);
            qcloud[ _nav + '_timer' ] = setTimeout(function () {
                $('[_t_nav]').each(function () {
                    $(this)[ _nav == $(this).attr('_t_nav') ? 'addClass' : 'removeClass' ]('nav-up-selected');
                });
                $('#' + _nav).stop(true, true).slideDown(200);
            }, 150);
        }, function () {
            var _nav = $(this).attr('_t_nav');
            clearTimeout(qcloud[ _nav + '_timer' ]);
            qcloud[ _nav + '_timer' ] = setTimeout(function () {
                $('[_t_nav]').removeClass('nav-up-selected');
                $('#' + _nav).stop(true, true).slideUp(200);
            }, 150);
        });
    });
</script>


<div class="head-v3">
    <div class="navigation-up">
        <div class="navigation-inner">
            <div class="navigation-v3">
                <ul>
                    <?php
                    if (isset($menuList) && !empty($menuList)) {
                        foreach ($menuList as $ke => $valu) {
                            ?>
                            <li class="" _t_nav="<?php echo $valu['menu_icon']; ?>">
                                <?php echo $valu['menu_name']; ?><span class="fa arrow"></span>
                            </li>
                            <?php
                        }
                    }
                    ?>
                </ul>
            </div>
        </div>
    </div>
    <div class="navigation-down" style="display: block;">
        <?php
        if (isset($menuList) && !empty($menuList)) {
            foreach ($menuList as $ke => $valu) { ?>
                <div id="<?php echo $valu['menu_icon']; ?>" class="nav-down-menu menu-1" style="display: none;" _t_nav="<?php echo $valu['menu_icon']; ?>">

                    <?php if (isset($valu['children']) && !empty($valu['children'])) {
                        ?>
                        <div class="navigation-down-inner">
                            <dl style="margin-left: 20px;width: 100%;">
                                <?php foreach ($valu['children'] as $secondMenu) { ?>
                                    <dd><a href="<?php echo $secondMenu['route'] == '' ? 'javascript:void(0);' : $secondMenu['route']; ?>"><?php echo $secondMenu['menu_name']; ?></a>
                                        <?php if (isset($secondMenu['children']) && !empty($secondMenu['children'])) { ?>
                                                    <ul>
                                                        <?php foreach ($secondMenu['children'] as $thirdMenu) {
                                                            if(!empty($thirdMenu['menu_name'])){
                                                            ?>
                                                            <li>
                                                                <a href="<?php echo $thirdMenu['route'] == '' ? 'javascript:void(0);' : $thirdMenu['route']; ?>">
                                                                    <?php echo $thirdMenu['menu_name']; ?>
                                                                </a>
                                                            </li>
                                                        <?php } }?>
                                                    </ul>
                                                <?php } ?>
                                    </dd>
                                <?php } ?>    
                            </dl>
                        </div>
                    <?php } ?>
                </div>
                <?php
            }
        }
        ?>
    </div>
</div>

