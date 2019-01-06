<?php

namespace app\modules\mails\components;

use Yii;
use app\components\GridViewAsset;
use yii\widgets\BaseListView;
use yii\helpers\Json;

/**
 * @author mrlin <714480119@qq.com>
 * @package ~???
 */

class GridView extends \app\components\GridView
{
	/**
	 * @inheritdoc
	 * 
	 */
	public function run()
	{
        $id = $this->options['id'];
        $options = Json::htmlEncode($this->getClientOptions());
        $view = $this->getView();
        GridViewAsset::register($view);
        $this->renderJsScript();
        BaseListView::run();
	}

    /**
     * @desc render js script
     */
    public function renderJsScript()
    {
        $this->jsScript[] = '$(function(){';
        $this->jsScript[] = '$("#' . $this->tableId . '").bootstrapTable({';
        $this->jsScript[] = 'url: "' . Yii::$app->request->getUrl() . '",';
        $this->jsScript[] = 'toolbar: "#toolbar",';
        $this->jsScript[] = 'method: "post",';
        $this->jsScript[] = 'contentType: "application/x-www-form-urlencoded",';
        $this->jsScript[] = 'pagination: true,';
        $this->jsScript[] = 'sidePagination: "server",';
        $this->jsScript[] = 'pageNumber: ' . ($this->dataProvider->getPagination()->getPage() + 1) . ',';
        $this->jsScript[] = 'pageSize: ' . $this->dataProvider->getPagination()->getPageSize() . ',';
        $this->jsScript[] = 'totalRows: ' . $this->dataProvider->getTotalCount() . ',';
        $this->jsScript[] = 'idField: "id",';
        $this->jsScript[] = 'selectItemName: "id",';
        $this->jsScript[] = 'clickToSelect: true,';
        $this->jsScript[] = 'pageList: [],';
        $this->jsScript[] = 'showPaginationSwitch: false,';
        $this->jsScript[] = 'queryParams: function(params){';
        $this->jsScript[] = 'var pageSize = params.limit;';
        $this->jsScript[] = 'var offset = params.offset;';
        $this->jsScript[] = 'var page = offset / pageSize + 1;';
        $this->jsScript[] = 'if (page < 1)';
        $this->jsScript[] = 'page = 1;';
        $this->jsScript[] = 'return {';
        $this->jsScript[] = 'pageSize: params.limit, //页面大小';
        $this->jsScript[] = 'page: page, //页码';
        $this->jsScript[] = 'sortBy: params.sort,';
        $this->jsScript[] = 'sortOrder: params.order,';
        $this->jsScript[] = '};';
        $this->jsScript[] = '},';
        $this->renderColumnScript();
        $this->jsScript[] = '});';
        $this->jsScript[] = '$(\'#search-form\').submit(function(){
        	var form = $(this);
        	var url = $(this).attr(\'action\') || window.location;
            var postData = $(this).serialize();
            $.ajax({
                url: url,
                type: \'post\',
                dataType: \'json\',
                data: postData,
                success: function(data){
                    $(\'#grid-list\').bootstrapTable(\'load\', data);
                },
                error: function(xhr, errstr, errno) {
                    
                }
            });
            return false;
        });';
        $this->jsScript[] = '});';
        $jsScriptString = implode("\n", $this->jsScript);
        $this->getView()->registerJs($jsScriptString);
    }

}