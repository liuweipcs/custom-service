/*!
 * Start Bootstrap - SB Admin 2 v3.3.7+1 (http://startbootstrap.com/template-overviews/sb-admin-2)
 * Copyright 2013-2016 Start Bootstrap
 * Licensed under MIT (https://github.com/BlackrockDigital/startbootstrap/blob/gh-pages/LICENSE)
 */
$(function() {
    $('#side-menu').metisMenu();
});
//全局使用。即所有弹出层都默认采用，但是单个配置skin的优先级更高
layer.config({
  skin: 'demo-class'
})
//ajax全局设置
var _shadowIndex = null;
$(document).ajaxStart(function() {
	_shadowIndex = layer.load(3, {
		area: ['100%', '100%'],
		shade: 0.2,
		offset: ['50%', '50%']
	});
});
var bootstrapTable;
$(document).ajaxError(function(event, xhr, errno, error) {
	layer.alert('请求失败，错误信息[' + error + ']，请重试！', {
		icon: 5
	}, function(index) {
		window.layer.close(index);
		layer.close(_shadowIndex);
	});	
});

$(document).ajaxSuccess(function() {
	layer.close(_shadowIndex);
});

/** ajax提交form表单 **/ 
function ajaxSubmit(form, data) {
	var url = form.attr('action');
	if (typeof(url) == 'undefined')
		url = window.location;

    //防止重复提交
    var token = $('#_token_').val();
    if(token){
         if($("#_token_").val() !== 1 && $("#_token_").val() !== "1")
            layer.msg('您已提交，请勿重复提交！', {
                            icon: 5,time:2000
                        });
    }

	var postData = form.serialize();
	data = data || '';
	//$.extend(postData, data);
	postData += '&' + data;
    if(token){
        token = $('#_token_').val(2);
    }
	$.ajax({
		type: 'POST',
		async: true,
		dataType: 'json',
		data: postData,
		url: url,
		success: function(data) {
            if (data.code != '200')
            {

                if(data.alertormsg == 'msg')
                {
                    layer.msg(data.message, {
                        icon: 5,time:2000
                    });
                }
                else
                {
                    layer.alert(data.message, {
                        icon: 5
                    });
                }
            }
			else {
                if(data.alertormsg == 'msg')
                {
                    layer.msg(data.message, {
                        icon: 1, time:1000
                    }, function(index) {
                        if (typeof(data.js) != 'undefined')
                            eval(data.js);
                        if (typeof(data.url) != 'undefined') {
                            window.location = data.url;
                        } else {
                            if (data.refresh)
							{
								if(data.top != 'undefined')
                                    window.top.location.reload();
								else
                                    window.location.reload();
                            }


                        }
                        if (typeof(data.closePopup) != 'undefined' && data.closePopup)
                            parent.layer.closeAll('iframe');
                        layer.close(index);
                    });
                }
                else
                {
                    layer.alert(data.message, {
                        icon: 1
                    }, function(index) {
                        if (typeof(data.js) != 'undefined')
                            eval(data.js);
                        if (typeof(data.url) != 'undefined') {
                            window.location = data.url;
                        } else {
                            if (data.refresh)
                            {
                                if(data.top != 'undefined')
                                    window.top.location.reload();
                                else
                                    window.location.reload();
                            }
                        }
                        if (typeof(data.closePopup) != 'undefined' && data.closePopup)
                            parent.layer.closeAll('iframe');
                        layer.close(index);
                        if(token){
                            token = $('#_token_').val(1);
                        }

                    });

                }

			}
		},
	});
}

/**
 * @desc 删除记录
 * @param id
 * @param obj
 * @param e
 * @returns {Boolean}
 */
function deleteRecord(id, obj, e)
{
	e.preventDefault();
	var url = $(obj).attr('href');
	if (url == '')
		return false;
	var confirmText = $(obj).attr('confirm');
	if (typeof(confirmText) != 'undefined' && !confirm($(obj).attr('confirm'))) return false;
	var queryStr = 'id=' + id;
	if (url.indexOf('?') != -1)
		url += '&' + queryStr;
	else
		url += '?' + queryStr;
	$.get(url, [], function(data) {
        if (data.code != '200') {
            if(data.alertormsg == 'msg')
            {
                layer.msg(data.message, {
                    icon: 5
                });
            }
            else
            {
                layer.alert(data.message, {
                    icon: 5
                });
            }
		} else {
            if(data.alertormsg == 'msg')
            {
                layer.msg(data.message, {
                    icon: 1
                }, function(index) {
                    if (typeof(data.js) != 'undefined')
                        eval(data.js);
                    if (typeof(data.url) != 'undefined') {
                        top.window.location = data.url;
                    } else {
                        if (data.refresh)
                            top.window.location.reload();
                    }
                    top.window.layer.close(index);
                });
            }
            else
            {
                layer.alert(data.message, {
                    icon: 1
                }, function(index) {
                    if (typeof(data.js) != 'undefined')
                        eval(data.js);
                    if (typeof(data.url) != 'undefined') {
                        top.window.location = data.url;
                    } else {
                        if (data.refresh)
                            top.window.location.reload();
                    }
                    top.window.layer.close(index);
                });
            }
        }
	}, 'json');
	return true;
}

/**
 * @desc 修改记录
 * @param id
 * @param obj
 * @param e
 * @returns {Boolean}
 */
function editRecord(id, obj, e)
{
	e.preventDefault();
	var title = $(this).attr('title');
	var url = $(obj).attr('href');
	if (url == '')
		return false;
	var confirmText = $(obj).attr('confirm');
	if (typeof(confirmText) != 'undefined' && !confirm($(obj).attr('confirm'))) return false;
	var queryStr = 'id=' + id;
	if (url.indexOf('?') != -1)
		url += '&' + queryStr;
	else
		url += '?' + queryStr;
	if (typeof(title) == 'undefined')
		title = $(obj).text();
	var width = $(obj).attr('_width');
	if (typeof(width) == 'undefined')
		width = '48%';
	var height = $(obj).attr('_height');
	if (typeof(height) == 'undefined')
		height = '48%';
	var index = layer.open({
		type: 2,
		title: title,
		content: url,
		area: [width, height]
	});
	return true;
}

/**
 * @desc 审核退货退款
 * @param id
 * @param obj
 * @param e
 * @returns {Boolean}
 */
function auditRecord(id, obj, e)
{
    e.preventDefault();
    
    var result = $.ajax({url:"/shouhou/refund/getauditresult",data:{id:id},async: false});
    var result = result.responseText;
            
    if (result == 'audit') {
        layer.alert('该申请已经审核过了，亲!', {
            icon: 5
        });
        return false;
    }

    var title = $(this).attr('title');
    var url = $(obj).attr('href');
    if (url == '')
        return false;
    var confirmText = $(obj).attr('confirm');
    if (typeof(confirmText) != 'undefined' && !confirm($(obj).attr('confirm'))) return false;
    var queryStr = 'return_refund_id=' + id;
    if (url.indexOf('?') != -1)
        url += '&' + queryStr;
    else
        url += '?' + queryStr;
    if (typeof(title) == 'undefined')
        title = $(obj).text();
    var width = $(obj).attr('_width');
    if (typeof(width) == 'undefined')
        width = '48%';
    var height = $(obj).attr('_height');
    if (typeof(height) == 'undefined')
        height = '48%';
    var index = layer.open({
        type: 2,
        title: title,
        content: url,
        area: [width, height]
    });
    return true;
}

function queryByTag(obj, tagId, type)
{
    var $this = $(obj);
	switch(type)
	{
		case 'headSummary':
			if($('#search-form').find('#headSummary').length == 0)
			{
				$('#search-form').append($('#headSummary'));
			}
			$('#headSummary').val(tagId);
			break;
		default:
			$('input[name=tag_id]').val(tagId);
	}
	$this.parents('.list-inline').find('a.tag-label').each(function(){
	    if (obj == this){
		    $(this).removeClass('label-off').addClass('label-on');
	    } else {
		    $(this).removeClass('label-on');
	    }
	});
	$('#search-form').submit();
	return;
}

function queryBySite(obj, siteId)
{
    var $this = $(obj);
    $('input[name=site_id]').val(siteId);

    $this.parents('.list-inline').find('a.site-label').each(function(){
        if (obj == this){
            $(this).removeClass('label-off').addClass('label-on');
        } else {
            $(this).removeClass('label-on');
        }
    });
    $('#search-form').submit();
    return;
}

function queryByAccount(obj, accountId)
{
	var $this = $(obj);
	//$("select option[value='"+accountId+"']").attr('selected',true);
	$('input[name=account_id]').val(accountId);

	$this.parents('.list-inline').find('a.account-label').each(function(){
		if (obj == this){
			$(this).removeClass('label-off').addClass('label-on');
		} else {
			$(this).removeClass('label-on');
		}
	});
	$('#search-form').submit();
	return;
}

/**
 * @desc ajax提示信息回调函数
 * @param data
 */
function ajaxMessageCallback(data)
{
	if (data.code != '200') {
		layer.alert(data.message, {
			icon: 5
		});
	} else {
		layer.alert(data.message, {
			icon: 1
		}, function(index) {
			if (typeof(data.js) != 'undefined')
				eval(data.js);
			if (typeof(data.url) != 'undefined') {
				window.location = data.url;
				//top.window.location = data.url;
			} else {
				if (data.refresh)
					window.location.reload();
					//top.window.location.reload();
			}
			window.layer.close(index);
			//top.window.layer.close(index);
		});
	}	
}

/**
 * @desc 注册按钮事件
 */
function registerButtonEvent()
{
    	$('a.delete-button, a.cancel-button, a.ajax-button').unbind('click').bind('click', function(e) {
    		e.preventDefault();
    		var url = $(this).attr('href');
    		if (url == '')
    			return false;
    		var confirmText = $(this).attr('confirm');
    		if (typeof(confirmText) != 'undefined' && !confirm($(this).attr('confirm'))) return false;
    		var queryStr = '';
    		var dataSrc = $(this).attr('data-src');
    		if (typeof(dataSrc) != 'undefined') {
    			var checkBox = $('input[name=' + dataSrc + ']:checked');
    			if (checkBox.length <= 0) {
    				layer.alert('没有选中数据', {
    					icon: 5
    				});
    				return false;
    			}
    			checkBox.each(function() {
    				queryStr += 'ids[]=' + $(this).val() + '&';
    			});
    			//queryStr = queryStr.substr(0, queryStr.length - 1);
    		}
    		//if (queryStr != '')
    			//url += '?' + queryStr;
    		$.post(url, queryStr, function(data) {
    			if (data.code != '200') {
    				layer.alert(data.message, {
    					icon: 5
    				});
    			} else {
    				layer.alert(data.message, {
    					icon: 1
    				}, function(index) {
    					if (typeof(data.js) != 'undefined')
    						eval(data.js);
    					if (typeof(data.url) != 'undefined') {
    						window.location = data.url;
    						//top.window.location = data.url;
    					} else {
    						if (data.refresh)
    							window.location.reload();
    							//top.window.location.reload();
    					}
    					window.layer.close(index);
    					//top.window.layer.close(index);
    				});
    			}
    		}, 'json');
    	});

    	//add button
    	$('a.edit-button, a.add-button, a.import-button').unbind('click').click(function(e) {
    		e.preventDefault();
    		var url = $(this).attr('href');
    		var title = $(this).attr('title');
    		if (typeof(title) == 'undefined')
    			title = $(this).text();
    		var width = $(this).attr('_width');
    		if (typeof(width) == 'undefined')
    			width = '48%';
    		var height = $(this).attr('_height');
    		if (typeof(height) == 'undefined')
    			height = '48%';
    		var index = layer.open({
    			type: 2,
    			title: title,
    			content: url,
    			area: [width, height]
    		});
    		return false;
    	});
        $('a.add-tags-button').unbind('click').click(function(e) {
            e.preventDefault(); 
            var url = $(this).attr('href');
    		if (url == '')
    			return false;
    		var confirmText = $(this).attr('confirm');
    		if (typeof(confirmText) != 'undefined' && !confirm($(this).attr('confirm'))) return false;
    		var queryStr = '';
    		var dataSrc = $(this).attr('data-src');
    		if (typeof(dataSrc) != 'undefined') {
    			var checkBox = $('input[name=' + dataSrc + ']:checked');
    			if (checkBox.length <= 0) {
    				layer.alert('没有选中数据', {
    					icon: 5
    				});
    				return false;
    			}
    			checkBox.each(function() {
    				queryStr += $(this).val() + ',';
    			});
    			queryStr = queryStr.substr(0, queryStr.length - 1);
    		}
    		var url = url + "?ids=" + queryStr + "&type=list";

    		var title = $(this).attr('title');
    		if (typeof(title) == 'undefined')
    			title = $(this).text();
    		var width = $(this).attr('_width');
    		if (typeof(width) == 'undefined')
    			width = '48%';
    		var height = $(this).attr('_height');
    		if (typeof(height) == 'undefined')
    			height = '48%';
    		var index = layer.open({
    			type: 2,
    			title: title,
    			content: url,
    			area: [width, height]
    		});
    		return false;
        });

        $('a.add-tags-button-button').unbind('click').click(function(e) {
            e.preventDefault(); 
            var url = $(this).attr('href');
    		if (url == '')
    			return false;
       
    		var title = $(this).attr('title');
    		if (typeof(title) == 'undefined')
    			title = $(this).text();
    		var width = $(this).attr('_width');
    		if (typeof(width) == 'undefined')
    			width = '48%';
    		var height = $(this).attr('_height');
    		if (typeof(height) == 'undefined')
    			height = '48%';
    		var index = layer.open({
    			type: 2,
    			title: title,
    			content: url,
    			area: [width, height]
    		});
    		return false;
        });

        
    	//关闭弹出层
    	$('a.close-button, button.close-button').unbind('click').click(function() {
            //alert(123);
    		//window.layer.closeAll();
    		top.window.layer.closeAll();
    	});
    	$('button.ajax-submit, div.ajax-submit').unbind('click').click(function() {
    		ajaxSubmit($(this).parents('form'));
    	});
    	$('button.submit-button').unbind('click').click(function() {
    		$(this).parents('form').submit();
    	});
}   	

/**
 * @desc 刷新表内容
 * @param url
 */
function refreshTable(url)
{
	$('#grid-list').bootstrapTable('refresh', {url: url});
}

//Loads the correct sidebar on window load,
//collapses the sidebar on window resize.
// Sets the min-height of #page-wrapper to window size
$(function() {
	//注册按钮事件
	registerButtonEvent();
    $(window).bind("load resize", function() {
        var topOffset = 50;
        var width = (this.window.innerWidth > 0) ? this.window.innerWidth : this.screen.width;
        if (width < 768) {
            $('div.navbar-collapse').addClass('collapse');
            topOffset = 100; // 2-row-menu
        } else {
            $('div.navbar-collapse').removeClass('collapse');
        }

        var height = ((this.window.innerHeight > 0) ? this.window.innerHeight : this.screen.height) - 1;
        height = height - topOffset;
        if (height < 1) height = 1;
        if (height > topOffset) {
            $("#page-wrapper").css("min-height", (height) + "px");
        }
    });

    var url = window.location;
    // var element = $('ul.nav a').filter(function() {
    //     return this.href == url;
    // }).addClass('active').parent().parent().addClass('in').parent();
    var element = $('ul.nav a').filter(function() {
        return this.href == url;
    }).addClass('active').parent();

    while (true) {
        if (element.is('li')) {
            element = element.parent().addClass('in').parent();
        } else {
            break;
        }
    }
    registerButtonEvent();
});
