<style>
    @CHARSET "UTF-8";
    /*= Reset =*/
    body,div,dl,dt,dd,ul,ol,li,h1,h2,h3,h4,h5,h6,pre,code,form,fieldset,legend,p,blockquote,th,td,figure{margin:0;padding:0;}
    article,aside,details,figcaption,figure,footer,header,hgroup,menu,nav,section{display:block;}
    table{border-collapse:collapse;border-spacing:0;}
    caption,th{font-weight:normal;text-align:left;}
    fieldset,img{border:0;}
    ul li{list-style:none;}
    h1,h2,h3,h4,h5,h6{font-size:100%;}
    blockquote:before,blockquote:after,q:before,q:after{content:"";}
    html{-webkit-text-size-adjust:none;-ms-text-size-adjust:none;}
    body{font:normal 14px/24px "Helvetica Neue",Helvetica,STheiti,"Microsoft Yahei","鍐潚榛戜綋绠€浣撲腑鏂� w3",瀹嬩綋,Arial,Tahoma,sans-serif,serif;word-wrap:break-word;background: #F0F0F0;}
    input,button,textarea,select,option,optiongroup{font-family:inherit;font-size:inherit;}
    *:focus{outline:0;}
    legend{color:#000;}
    input,select{vertical-align:middle;}
    button{overflow:visible;}
    input.button,button{cursor:pointer;}
    button,input,select,textarea{margin:0;}
    textarea{overflow:auto;resize:none;}
    label[for],input[type="button"],input[type="submit"],input[type="reset"]{cursor:pointer;}
    input[type="button"]::-moz-focus-inner,input[type="submit"]::-moz-focus-inner,input[type="reset"]::-moz-focus-inner,button::-moz-focus-inner{border:0;padding:0;}
    a{text-decoration:none;color:#1C3D72 }
    img{-ms-interpolation-mode:bicubic;}
    /* new clearfix */
    .clearfix:after {visibility: hidden;display: block;font-size: 0;content: " ";clear: both;height: 0;}
    * html .clearfix{ zoom: 1;}
    /* IE6 */
    *:first-child+html .clearfix{zoom: 1;}
    /* IE7 */
    .hidden{display:none;}
    .last{border-bottom:none !important;}

    /* page */
    .page{display:table;margin:0 auto;background:#fff;-moz-box-shadow: 0 5px 20px #CCCCCC;-webkit-box-shadow: 0 5px 20px #CCCCCC;box-shadow: 0 5px 20px #CCCCCC;}
    .about{box-shadow:0;-webkit-box-shadow:0;-moz-box-shadow:0;}
    .header{width:940px;height:90px;margin:0 auto;z-index:8;}
    .logo{margin:22px 0 0 0;float:left;display:inline;}
    .link{margin-top:30px;float:right;text-align:right;_width:718px;}
    .link li{float:left;display:inline;margin-left:60px;}
    .link li a{color:#4F4E4E;font-size:16px;font-weight:500;padding-bottom:6px;display:block;}
    .link li.active{border-bottom:2px solid #0066ff;}
    .link li.active a{color:#0066FF  }
    .link li:hover{border-bottom:2px solid #0066ff;color:#0066FF  }
    .link li a:hover{color:#0066FF  }.box{width:940px;margin:18px auto 0 auto;}
    .event_year{width:60px;border-bottom:2px solid #DDD;text-align:center;float:left;margin-top:10px;}
    .event_year li{height:40px;line-height:40px;background:#FFF;margin-bottom:1px;font-size:18px;color:#828282;cursor:pointer;}
    .event_year li.current{width:61px;background:#0066ff url('/img/jian.png') 60px 0 no-repeat;color:#FFF;text-align:left;padding-left:9px;}
    .event_list{width:850px;float:right;background:url('/img/dian3.png') 139px 0 repeat-y;margin:10px 0 20px 0;}
    .event_list h3{margin:0 0 10px 132px;font-size:24px;font-family:Georgia;color:#0066ff;padding-left:25px;background:url('/img/jian.png') 0 -45px no-repeat;height:38px;line-height:30px;font-style:italic;}.event_list li{background:url('/img/jian.png') 136px -80px no-repeat;}
    .event_list li span{width:auto;text-align:right;display:block;float:left;margin-top:10px;}
    .event_list li p{width:80%;margin-left:24px;display:inline-block;padding-left:10px;margin-bottom: 20px;background:url('/img/jian.png') -21px 0 no-repeat;line-height:25px;_float:left;}
    .event_list li p span{width:650px;text-align:left;border-bottom:2px solid #DDD;padding:10px 15px;background:#FFF;margin:0;}
</style>
<div class="box">
    <ul class="event_list">
        <?php
        if (!empty($data['logistics_detail'])) {?>
        <h3><a href="https://t.17track.net/en#nums=<?php echo $trackNumber;?>" target="_blank"> 去官网查看</a></h3>    
        <?php    foreach ($data['logistics_detail'] as $k => $val) {
                ?>
                <li>
                    <span><?php echo $val['eventTime']; ?></span>
                    <p>
                        <span>
                            <?php echo $val['eventThing']; ?>
                            <?php echo !empty($val['eventDetail']) ? '('.$val['eventDetail'].')' : "";?>
                            【<?php echo $val['place'];?>】</span><br/>
                    </p>
                    
                </li>
            <?php }
        } else {
            ?>
                <h3>系统暂未抓取到当前订单的物流数据  <a href="https://t.17track.net/en#nums=<?php echo $trackNumber;?>" target="_blank">去官网查看</a></h3>
    <?php } ?>
    </ul>
    <div class="clearfix"></div>
</div>