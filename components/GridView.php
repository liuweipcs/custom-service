<?php
namespace app\components;
use Yii;
use Closure;
use yii\i18n\Formatter;
use yii\base\InvalidConfigException;
use yii\helpers\Url;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\widgets\BaseListView;
use app\components\GridViewAsset;
use yii\db\ActiveRecord;
use kartik\select2\Select2;
use kartik\datetime\DateTimePicker;
class GridView extends BaseListView
{
    const FILTER_POS_HEADER = 'header';
    const FILTER_POS_FOOTER = 'footer';
    const FILTER_POS_BODY = 'body';
    
    /**
     * @var string the default data column class if the class name is not explicitly specified when configuring a data column.
     * Defaults to 'yii\grid\DataColumn'.
     */
    public $dataColumnClass;
    /**
     * @var string the caption of the grid table
     * @see captionOptions
     */
    public $caption;
    /**
     * @var array the HTML attributes for the caption element.
     * @see \yii\helpers\Html::renderTagAttributes() for details on how attributes are being rendered.
     * @see caption
     */
    public $captionOptions = [];
    /**
     * @var array the HTML attributes for the grid table element.
     * @see \yii\helpers\Html::renderTagAttributes() for details on how attributes are being rendered.
    */
    public $tableOptions = ['class' => 'table table-striped table-bordered'];
    /**
     * @var array the HTML attributes for the container tag of the grid view.
     * The "tag" element specifies the tag name of the container element and defaults to "div".
     * @see \yii\helpers\Html::renderTagAttributes() for details on how attributes are being rendered.
    */
    public $options = ['class' => 'grid-view'];
    /**
     * @var array the HTML attributes for the table header row.
     * @see \yii\helpers\Html::renderTagAttributes() for details on how attributes are being rendered.
    */
    public $headerRowOptions = [];
    /**
     * @var array the HTML attributes for the table footer row.
     * @see \yii\helpers\Html::renderTagAttributes() for details on how attributes are being rendered.
    */
    public $footerRowOptions = [];
    /**
     * @var array|Closure the HTML attributes for the table body rows. This can be either an array
     * specifying the common HTML attributes for all body rows, or an anonymous function that
     * returns an array of the HTML attributes. The anonymous function will be called once for every
     * data model returned by [[dataProvider]]. It should have the following signature:
     *
     * ```php
     * function ($model, $key, $index, $grid)
     * ```
     *
     * - `$model`: the current data model being rendered
     * - `$key`: the key value associated with the current data model
     * - `$index`: the zero-based index of the data model in the model array returned by [[dataProvider]]
     * - `$grid`: the GridView object
     *
     * @see \yii\helpers\Html::renderTagAttributes() for details on how attributes are being rendered.
    */
    public $rowOptions = [];
    /**
     * @var Closure an anonymous function that is called once BEFORE rendering each data model.
     * It should have the similar signature as [[rowOptions]]. The return result of the function
     * will be rendered directly.
    */
    public $beforeRow;
    /**
     * @var Closure an anonymous function that is called once AFTER rendering each data model.
     * It should have the similar signature as [[rowOptions]]. The return result of the function
     * will be rendered directly.
     */
    public $afterRow;
    /**
     * @var bool whether to show the header section of the grid table.
     */
    public $showHeader = true;
    /**
     * @var bool whether to show the footer section of the grid table.
     */
    public $showFooter = false;
    /**
     * @var bool whether to show the grid view if [[dataProvider]] returns no data.
     */
    public $showOnEmpty = true;
    /**
     * @var array|Formatter the formatter used to format model attribute values into displayable texts.
     * This can be either an instance of [[Formatter]] or an configuration array for creating the [[Formatter]]
     * instance. If this property is not set, the "formatter" application component will be used.
     */
    public $formatter;
    /**
     * @var array grid column configuration. Each array element represents the configuration
     * for one particular grid column. For example,
     *
     * ```php
     * [
     *     ['class' => SerialColumn::className()],
     *     [
     *         'class' => DataColumn::className(), // this line is optional
     *         'attribute' => 'name',
     *         'format' => 'text',
     *         'label' => 'Name',
     *     ],
     *     ['class' => CheckboxColumn::className()],
     * ]
     * ```
     *
     * If a column is of class [[DataColumn]], the "class" element can be omitted.
     *
     * As a shortcut format, a string may be used to specify the configuration of a data column
     * which only contains [[DataColumn::attribute|attribute]], [[DataColumn::format|format]],
     * and/or [[DataColumn::label|label]] options: `"attribute:format:label"`.
     * For example, the above "name" column can also be specified as: `"name:text:Name"`.
     * Both "format" and "label" are optional. They will take default values if absent.
     *
     * Using the shortcut format the configuration for columns in simple cases would look like this:
     *
     * ```php
     * [
     *     'id',
     *     'amount:currency:Total Amount',
     *     'created_at:datetime',
     * ]
     * ```
     *
     * When using a [[dataProvider]] with active records, you can also display values from related records,
     * e.g. the `name` attribute of the `author` relation:
     *
     * ```php
     * // shortcut syntax
     * 'author.name',
     * // full syntax
     * [
     *     'attribute' => 'author.name',
     *     // ...
     * ]
     * ```
     */
    public $columns = [];
    /**
     * @var string the HTML display when the content of a cell is empty.
     * This property is used to render cells that have no defined content,
     * e.g. empty footer or filter cells.
     *
     * Note that this is not used by the [[DataColumn]] if a data item is `null`. In that case
     * the [[\yii\i18n\Formatter::nullDisplay|nullDisplay]] property of the [[formatter]] will
     * be used to indicate an empty data value.
    */
    public $emptyCell = '&nbsp;';
    /**
     * @var \yii\base\Model the model that keeps the user-entered filter data. When this property is set,
     * the grid view will enable column-based filtering. Each data column by default will display a text field
     * at the top that users can fill in to filter the data.
     *
     * Note that in order to show an input field for filtering, a column must have its [[DataColumn::attribute]]
     * property set and the attribute should be active in the current scenario of $filterModel or have
     * [[DataColumn::filter]] set as the HTML code for the input field.
     *
     * When this property is not set (null) the filtering feature is disabled.
     */
    public $filterModel;
    /**
     * @var string|array the URL for returning the filtering result. [[Url::to()]] will be called to
     * normalize the URL. If not set, the current controller action will be used.
     * When the user makes change to any filter input, the current filtering inputs will be appended
     * as GET parameters to this URL.
     */
    public $filterUrl;
    /**
     * @var string additional jQuery selector for selecting filter input fields
     */
    public $filterSelector;
    /**
     * @var string whether the filters should be displayed in the grid view. Valid values include:
     *
     * - [[FILTER_POS_HEADER]]: the filters will be displayed on top of each column's header cell.
     * - [[FILTER_POS_BODY]]: the filters will be displayed right below each column's header cell.
     * - [[FILTER_POS_FOOTER]]: the filters will be displayed below each column's footer cell.
     */
    public $filterPosition = self::FILTER_POS_BODY;
    /**
     * @var array the HTML attributes for the filter row element.
     * @see \yii\helpers\Html::renderTagAttributes() for details on how attributes are being rendered.
     */
    public $filterRowOptions = ['class' => 'filters'];
    /**
     * @var array the options for rendering the filter error summary.
     * Please refer to [[Html::errorSummary()]] for more details about how to specify the options.
     * @see renderErrors()
    */
    public $filterErrorSummaryOptions = ['class' => 'error-summary'];
    /**
     * @var array the options for rendering every filter error message.
     * This is mainly used by [[Html::error()]] when rendering an error message next to every filter input field.
    */
    public $filterErrorOptions = ['class' => 'help-block'];
    /**
     * @var string the layout that determines how different sections of the list view should be organized.
     * The following tokens will be replaced with the corresponding section contents:
     *
     * - `{summary}`: the summary section. See [[renderSummary()]].
     * - `{errors}`: the filter model error summary. See [[renderErrors()]].
     * - `{items}`: the list items. See [[renderItems()]].
     * - `{sorter}`: the sorter. See [[renderSorter()]].
     * - `{pager}`: the pager. See [[renderPager()]].
    */
    public $layout = "{filters}{sites}{tags}{accountEmail}{headSummary}{toolBar}{jsScript}{items}";
    
    /**
     * @desc js script
     * @var unknown
     */
    public $jsScript = '';
    
    /**
     * @desc table id attribute
     * @var unknown
     */
    public $tableId = 'grid-list';
    
    /**
     * @desc active record model
     * @var unknown
     */
    public $model = null;
    
    /**
     * @desc toolBars
     * @var unknown
     */
    public $toolBars = null;
    
    /**
     * @desc tag div
     * @var unknown
     */
    public $tags = [];
    public $is_tags = false;

    public $sites = [];
    public $is_sites = false;

    public $account_email = [];

    public $headSummary = [];
    
    public $url = '';
    
    public $enableTools = true;
    
    /**
     * Initializes the grid view.
     * This method will initialize required property values and instantiate [[columns]] objects.
     */
    public function init()
    {
        parent::init();
        if ($this->formatter === null) {
            $this->formatter = Yii::$app->getFormatter();
        } elseif (is_array($this->formatter)) {
            $this->formatter = Yii::createObject($this->formatter);
        }
        if (!$this->formatter instanceof Formatter) {
            throw new InvalidConfigException('The "formatter" property must be either a Format object or a configuration array.');
        }
        if (!isset($this->filterRowOptions['id'])) {
            $this->filterRowOptions['id'] = $this->options['id'] . '-filters';
        }
        if (!isset($this->tableOptions['id']))
            $this->tableOptions['id'] = $this->tableId;
        if (!isset($this->tableOptions['width']))
            $this->tableOptions['width'] = '100%';
        $this->tableOptions['style'] = 'word-break:break-all;';
        if (!$this->model instanceof ActiveRecord)
            throw new InvalidConfigException('The "model" property must be a ActiveRecord Object.');
    }
    
    /**
     * Runs the widget.
     */
    public function run()
    {
        $id = $this->options['id'];
        $options = Json::htmlEncode($this->getClientOptions());
        $view = $this->getView();
        GridViewAsset::register($view);
/*         $this->renderFilters();
        $this->renderTags();
        $this->renderAccountEmail();
        $this->renderHeadSummary();
        $this->renderToolBar();
        $this->renderJsScript(); */
        parent::run();
    }
    
    /**
     * !CodeTemplates.overridecomment.nonjd!
     * @see \yii\widgets\BaseListView::renderSection()
     */
    public function renderSection($name)
    {
        switch ($name) {
            case '{summary}':
                return $this->renderSummary();
            case '{items}':
                return $this->renderItems();
            case '{pager}':
                return $this->renderPager();
            case '{sorter}':
                return $this->renderSorter();
            case '{filters}':
                return $this->renderFilters();
            case '{tags}':
                return $this->renderTags();
            case '{sites}':
                return $this->renderSites();
            case '{accountEmail}':
                return $this->renderAccountEmail();
            case '{headSummary}':
                return $this->renderHeadSummary();
            case '{toolBar}':
                return $this->renderToolBar();
            case '{jsScript}':
                return $this->renderJsScript();                                                                                                                                    
            default:
                return false;
        }
    }
    
    /**
     * @desc render js script
     */
    public function renderJsScript()
    {
        $this->jsScript[] = '$(function(){';
        $this->jsScript[] = '$("#' . $this->tableId . '").bootstrapTable({';
        $this->jsScript[] = 'url: "' . (!empty($this->url) ? $this->url : Yii::$app->request->getUrl()) . '",';
        $this->jsScript[] = 'toolbar: "#toolbar",';
        $this->jsScript[] = 'method: "post",';
        $this->jsScript[] = 'contentType: "application/x-www-form-urlencoded",';
        $this->jsScript[] = 'pagination: true,';
        $this->jsScript[] = 'paginationFirstText: "首页",';
        $this->jsScript[] = 'paginationPreText: "上一页",';
        $this->jsScript[] = 'paginationNextText: "下一页",';
        $this->jsScript[] = 'paginationLastText: "尾页",';
        $this->jsScript[] = 'sidePagination: "server",';
        $this->jsScript[] = 'pageNumber: ' . ($this->dataProvider->getPagination()->getPage() + 1) . ',';
        $this->jsScript[] = 'pageSize: ' . $this->dataProvider->getPagination()->getPageSize() . ',';
        $this->jsScript[] = 'totalRows: ' . $this->dataProvider->getTotalCount() . ',';
        $this->jsScript[] = 'pageList: [10, 20, 50, 100, 200, 500, 1000],';
        if ($this->enableTools)
        {
            $this->jsScript[] = 'showColumns: true,';
            $this->jsScript[] = 'showRefresh: true,';
            $this->jsScript[] = 'showPaginationSwitch: true,';
        }
        //$this->jsScript[] = 'detailView: true,';
        $this->jsScript[] = 'onSort: function(name, order){
            $("input[name=sortBy]").val(name);
            $("input[name=sortOrder]").val(order);
        },';
/*         $this->jsScript[] = 'customSearch: function(obj){alert(obj);
            var data = $("#search-form").serializeArray();
            var dataString = "{";
            for (var i in data)
            {
                var property = data[i].name;
                var value = data[i].value;
                dataString += "\"" + property + "\"" + ": \"" + value + "\",";
            }
            dataString = dataString.substr(dataString, dataString.length - 1);
            dataString += "}";
            return JSON.parse(dataString);
        },'; */
        $this->jsScript[] = 'onPostBody: function(){
            registerButtonEvent();
        },';
        $this->jsScript[] = 'idField: "id",';
        $this->jsScript[] = 'selectItemName: "id",';
        $this->jsScript[] = 'clickToSelect: true,';
        $this->jsScript[] = 'queryParams: function(params){';
        $this->jsScript[] = 'var pageSize = params.limit;';
        $this->jsScript[] = 'var offset = params.offset;';
        $this->jsScript[] = 'var page = offset / pageSize + 1;';
        $this->jsScript[] = 'if (page < 1)';
        $this->jsScript[] = 'page = 1;';
        $this->jsScript[] = 'var queryParam = {
            pageSize: params.limit, //页面大小
            page: page, //页码
            sortBy: params.sort,
            sortOrder: params.order,
        };
            var formData = $("#search-form").serializeArray();
            if (typeof(formData) != "undefined")
            {
                for (var i in formData)
                {   
                    var name = formData[i].name;
                    var data = {};
                    data[name] = formData[i].value;
                    $.extend(queryParam, data);
                }
            }
        ';
        $this->jsScript[] = 'return queryParam;';
        $this->jsScript[] = '},';
        //$this->jsScript[] = 'search: true,';
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
                    var page = data.pageNumber;
                    var pageSize = data.pageSize;
                   <!-- $(\'#grid-list\').bootstrapTable(\'refreshOptions\', {"pageNumber":page, "pageSize":pageSize});-->
                    $(\'#grid-list\').bootstrapTable(\'load\', data);
                    if (typeof(data.tagList) != "undefined")
                    {
                        var tagList = data.tagList;
                        var tagId = $("input[name=tag_id]").val();
                        var html = "<ul class=\"list-inline\">";
                        html += "<li><strong>自定义标签：</strong></li>";
                        html += "<li><a class=\"tag-label" + (tagId == "" ? " label-on" : " label-off") + "\" href=\"javascript:void(0);\" onclick=\"queryByTag(this, \'\')\">' . \Yii::t('system', 'All') . '</a></li>";
                        for (var i in tagList)
                        {
                            html += "<li><a class=\"tag-label" + (tagId == tagList[i].id ? " label-on" : " label-off") + "\" href=\"javascript:void(0);\" onclick=\"queryByTag(this, " + tagList[i].id + ")\">" + tagList[i].name + "(" + tagList[i].count + ")</a></li>";
                        }
                        html += "</ul>";
                        $("#tag-box").empty().html(html);
                    }
                    if (typeof(data.siteList) != "undefined")
                    {
                        var siteList = data.siteList;
                        var siteId = $("input[name=site_id]").val();
                        var html = "<ul class=\"list-inline\">";
                        html += "<li><strong>站点：</strong></li>";
                        html += "<li><a class=\"site-label" + (siteId == "" ? " label-on" : " label-off") + "\" href=\"javascript:void(0);\" onclick=\"queryBySite(this, \'\')\">' . \Yii::t('system', 'All') . '</a></li>";
                        for (var i in siteList)
                        {
                            html += "<li><a class=\"site-label" + (siteId == siteList[i].id ? " label-on" : " label-off") + "\" href=\"javascript:void(0);\" onclick=\"queryBySite(this, " + siteList[i].id + ")\">" + siteList[i].name + "(" + siteList[i].count + ")</a></li>";
                        }
                        html += "</ul>";
                        $("#site-box").empty().html(html);
                    }
                    if (typeof(data.account_email) != "undefined")
                    {
                        var account_email = data.account_email;
                        var accountId = $("input[name=account_id]").val();
                        var html = "<ul class=\"list-inline\">";
                        html += "<li><strong>帐号：</strong></li>";
                        html += "<li><a class=\"account-label" + (accountId == "" ? " label-on" : " label-off") + "\" href=\"javascript:void(0);\" onclick=\"queryByAccount(this, \'\')\">' . \Yii::t('system', 'All') . '</a></li>";
                        for (var i in account_email)
                        {
                            html += "<li><a class=\"account-label" + (accountId == account_email[i].id ? " label-on" : " label-off") + "\" href=\"javascript:void(0);\" onclick=\"queryByAccount(this, " + account_email[i].id + ")\">" + account_email[i].name + "(" + account_email[i].count + ")</a></li>";
                        }
                        html += "</ul>";
                        $("#account_email-box").empty().html(html);
                    }
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
    
    /**
     * @desc render column js script
     */
    public function renderColumnScript()
    {
        $this->jsScript[] = 'columns: [';
        foreach ($this->columns as $row)
        {
            $field = isset($row['field']) ? trim($row['field']) : '';
            if (empty($field)) continue;
            $this->jsScript[] = '{';
            if (isset($row['headerTitle']) && !empty($row['headerTitle']))
                $label = $row['headerTitle'];
            else
                $label = $this->model->getAttributeLabel($field);
            $this->jsScript[] = 'field: "' . $field . '",';
            $this->jsScript[] = 'title: "' . $label . '",';
            $this->jsScript[] = 'halign: "center",';
            $this->jsScript[] = 'valign: "middle",';
            if (isset($row['type']))
            {
                switch ($row['type'])
                {
                    case 'radio':
                        $this->jsScript[] = 'radio: true,';
                        break;
                    case 'checkbox':
                        $this->jsScript[] = 'checkbox: true,';
                        break;
                    case 'operateButton':
                        $buttonList = isset($row['buttons']) ? $row['buttons'] : [];
                        if (!is_array($buttonList) || empty($buttonList)) break;
                        $this->jsScript[] = 'formatter: function(value, row, index){
                                var html = \'<div class="btn-group btn-list">\' +
                                    \'<button type="button" class="btn btn-default btn-sm">' . Yii::t('system', 'Operation') . '</button>\' +
                                    \'<button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown">\' +
                                        \'<span class="caret"></span>\' +
                                        \'<span class="sr-only">' . Yii::t('system', 'Toggle Dropdown List') . '</span>\' +
                                    \'</button>\' +
                                    \'<ul class="dropdown-menu" rol="menu">\'+ ';
                        foreach ($buttonList as $button)
                        {
                            $text = isset($button['text']) ? $button['text'] : '';
                            $href = isset($button['href']) ? $button['href'] : '';
                            $queryParams = isset($button['queryParams']) ? $button['queryParams'] : [];
                            $condition = isset($button['condition']) ? trim($button['condition']) : '';
                            $queryString = '';
                            if (is_array($queryParams))
                            {
                                foreach ($queryParams as $key => $value)
                                {
                                    $queryString .= '&' . $key . '= \' + row.' . $value . ' + \'';
                                }
                                $queryString = trim($queryString, '&');
                            }
                            if(strpos($href, '?') === false)
                                $href .= '?' . $queryString;
                            else
                                $href .= '&' . $queryString;
                            if(!self::_aclcheck(Yii::$app->user->identity->id,$href))
                                continue;
                            
                            $columnHtmlOptions = isset($button['htmlOptions']) ? $button['htmlOptions'] : [];
                            $columnHtmlOptionString = '';
                            foreach ($columnHtmlOptions as $attr => $val)
                                $columnHtmlOptionString .= ' ' . $attr . ' = "' . $val . '"';
                            if (!empty($condition))
                            {
                                $this->jsScript[] = '((eval(' . $condition . ') ? 
                                    \'<li><a href="' . $href . '" ' . $columnHtmlOptionString . '>'
                                        . $text . '</a></li>\': "")) + ';
                            }
                            else
                                $this->jsScript[] = '\'<li><a href="' . $href . '" ' . $columnHtmlOptionString . '>' . $text . '</a></li>\' + ';
                        }                        
                        $this->jsScript[] = '\'</ul>\' +
                                \'</div>\';
                                return html;
                            },';
                        $this->jsScript[] = 'events: {
                            \'click .delete-record\': function(e, value, row, index) {
                                deleteRecord(row.id, this, e);
                            },
                            \'click .edit-record\': function(e, value, row, index){
                                editRecord(row.id, this, e);
                            },
                            \'click .return-refund-order\': function(e, value, row, index){
                                auditRecord(row.id, this, e);
                            },
                        },';
                        break;
                    case 'hrefOperateButton':
                        $buttonList = isset($row['buttons']) ? $row['buttons'] : [];
                        if (!is_array($buttonList) || empty($buttonList)) break;
                        $this->jsScript[] = 'formatter: function(value, row, index){
                                var html = \'<div class="btn-group btn-list">\' +
                                \'<a class="btn btn-default btn-sm edit-record" href="'.$row['href'].'" _width="100%" _height="100%">' . Yii::t('system', $row['text']) . '</a>\' +
                                    \'<button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown">\' +
                                        \'<span class="caret"></span>\' +
                                        \'<span class="sr-only">' . Yii::t('system', 'Toggle Dropdown List') . '</span>\' +
                                    \'</button>\' +
                                    \'<ul class="dropdown-menu" rol="menu">\'+ ';
                        foreach ($buttonList as $button)
                        {
                            $text = isset($button['text']) ? $button['text'] : '';
                            $href = isset($button['href']) ? $button['href'] : '';

                            $columnHtmlOptions = isset($button['htmlOptions']) ? $button['htmlOptions'] : [];
                            $columnHtmlOptionString = '';
                            foreach ($columnHtmlOptions as $attr => $val)
                                $columnHtmlOptionString .= ' ' . $attr . ' = "' . $val . '"';
                            $this->jsScript[] = '\'<li><a href="' . $href . '" ' . $columnHtmlOptionString . '>' . $text . '</a></li>\' + ';
                        }
                        $this->jsScript[] = '\'</ul>\' +
                                \'</div>\';
                                return html;
                            },';
                        $this->jsScript[] = 'events: {
                            \'click .delete-record\': function(e, value, row, index) {
                                deleteRecord(row.id, this, e);
                            },
                            \'click .edit-record\': function(e, value, row, index){
                                editRecord(row.id, this, e);
                            },
                            \'click .return-refund-order\': function(e, value, row, index){
                                auditRecord(row.id, this, e);
                            },
                        },';
                        break;
                    default:;                   
                }
            }
            if (isset($row['sortAble']) && $row['sortAble'])
                $this->jsScript[] = 'sortable: true,';
            $htmlOptions = [];
            if (isset($row['htmlOptions']) && is_array($row['htmlOptions']))
                $htmlOptions = $row['htmlOptions'];
            $class = isset($htmlOptions['class']) ? $htmlOptions['class'] : '';
            $style = isset($htmlOptions['style']) ? $htmlOptions['style'] : [];
            $styleScript = \yii\helpers\Json::encode($style);
            try
            {
                $styleScript = \yii\helpers\Json::encode($style);
            }
            catch (\Exception $e)
            {
                $styleScript = [];
            }
            if (!empty($class) || !empty($style))
            {
                $this->jsScript[] = 'cellStyle: function(){
                    return {
                        classes: "' . $class . '",
                        css: ' . $styleScript . '
                    };
                },';
            }
            if (isset($htmlOptions['align']))
                $this->jsScript[] = 'align: "' . $htmlOptions['align'] . '",';
            if (isset($htmlOptions['width']))
                $this->jsScript[] = 'width: "' . $htmlOptions['width'] . '",';
            $this->jsScript[] = '},';
        }
        $this->jsScript[] = '],';
    }
    
    /**
     * Renders validator errors of filter model.
     * @return string the rendering result.
     */
    public function renderErrors()
    {
        if ($this->filterModel instanceof Model && $this->filterModel->hasErrors()) {
            return Html::errorSummary($this->filterModel, $this->filterErrorSummaryOptions);
        } else {
            return '';
        }
    }
    
    /**
     * @inheritdoc
     */
/*     public function renderSection($name)
    {
        switch ($name) {
            case '{errors}':
                return $this->renderErrors();
            default:
                return parent::renderSection($name);
        }
    } */
    
    /**
     * Returns the options for the grid view JS widget.
     * @return array the options
     */
    protected function getClientOptions()
    {
        $filterUrl = isset($this->filterUrl) ? $this->filterUrl : Yii::$app->request->url;
        $id = $this->filterRowOptions['id'];
        $filterSelector = "#$id input, #$id select";
        if (isset($this->filterSelector)) {
            $filterSelector .= ', ' . $this->filterSelector;
        }
    
        return [
            'filterUrl' => Url::to($filterUrl),
            'filterSelector' => $filterSelector,
        ];
    }
    
    /**
     * Renders the data models for the grid view.
     */
    public function renderItems()
    {
        return Html::tag('table', '', $this->tableOptions);
    }
    
    /**
     * Renders the caption element.
     * @return bool|string the rendered caption element or `false` if no caption element should be rendered.
     */
    public function renderCaption()
    {
        if (!empty($this->caption)) {
            return Html::tag('caption', $this->caption, $this->captionOptions);
        } else {
            return false;
        }
    }
    
    /**
     * Renders the column group HTML.
     * @return bool|string the column group HTML or `false` if no column group should be rendered.
     */
    public function renderColumnGroup()
    {
        return [];
    }
    
    /**
     * Renders the table header.
     * @return string the rendering result.
     */
    public function renderTableHeader()
    {
        return [];
    }
    
    /**
     * Renders the table footer.
     * @return string the rendering result.
     */
    public function renderTableFooter()
    {
        return [];
    }
    
    /**
     * Renders the filter.
     * @return string the rendering result.
     */
    public function renderFilters()
    {
        //require_once \Yii::getAlias('@vendor').'/kartik-v/yii2-widget-Select2/Select2Asset.php';
        if ($this->model !== null) {
            $filterOptions = [];
            if (method_exists($this->model, 'filterOptions'))
                $filterOptions = $this->model->filterOptions();
            if (empty($filterOptions)) return false;
            echo Html::beginTag('div', ['class' => 'well']);
            echo Html::beginForm((!empty($this->url) ? $this->url : Yii::$app->request->getUrl()), 'post', [
                'id' => 'search-form',
                'role' => 'form',
                'class' => 'form-horizontal',
            ]);
            //添加排序隐藏域
            echo Html::hiddenInput('sortBy', '');
            echo Html::hiddenInput('sortOrder', '');
            echo Html::beginTag('ul', ['class' => 'list-inline']);
            foreach ($filterOptions as $options)
            {
                $name = isset($options['name']) ? trim($options['name']) : '';
                if (empty($name)) continue;
                $label = $this->model->getAttributeLabel($name);
                $type = isset($options['type']) ? trim($options['type']) : '';
                $data = isset($options['data']) ? $options['data'] : [];
                $value = isset($options['value']) ? $options['value'] : '';
                $htmlOptions = isset($options['htmlOptions']) ? $options['htmlOptions'] : [];
                $htmlOptions['class'] = isset($htmlOptions['class']) ? $htmlOptions['class'] . ' ' . 'form-control' : 'form-control';
                if ($type == 'hidden')
                {
                    echo Html::hiddenInput($name, $value, $htmlOptions);
                    continue;
                }
                echo Html::beginTag('li');
                echo Html::beginTag('div', ['class' => 'form-group']);
                echo Html::label($label, '', [
                    'class' => 'control-label col-lg-5',
               
                ]);
                echo Html::beginTag('div', ['class' => 'col-lg-7']);
                switch ($type)
                {
                    //case 'radio':
                    //case 'checkbox':
                    case 'dropDownList':
                        $optionData = ['' => Yii::t('system', 'All')];
                        $optionData = $optionData + $data;
                        $htmlOptions['style'] = isset($htmlOptions['style-width']) ? $htmlOptions['style-width'] . ';min-width:150px' : 'min-width:150px';
                        echo Html::dropDownList($name, $value, $optionData, $htmlOptions);
                        break;
                    case 'text':
                        echo Html::textInput($name, $value, ['class' => 'form-control']);
                        break;
                    case 'search':
                        echo Select2::widget([
                                'name' =>$name,
                                'data' =>$data,
                                'options' =>[
                                    'placeholder'=>'--请输入--',
                                ],
                                
                        ]);
                        break;
                    case 'date_picker':
                        $time_default_value = isset($options['value']) ? $value : date('Y-m-01 00:00:00',strtotime('-2 month'));
                        if($name != 'start_time') {
                            $time_default_value = isset($options['value']) ? $value : date('Y-m-d 23:59:59', strtotime("$time_default_value +3month -1 day"));
                        }
                        echo DateTimePicker::widget([
                            'name' => $name,
                            'value' => $time_default_value,
                            'options' => ['placeholder' => 'Select date ...','style' => 'width:160px;'],
                            'pluginOptions' => [
                                'format' => 'yyyy-mm-dd hh:ii:ss',
                                'todayHighlight' => true
                            ]
                        ]);
                        break;
                }
                echo Html::endTag('div');
                echo Html::endTag('div');
                echo Html::endTag('li');
            }
            echo Html::endTag('ul');
            echo Html::submitButton(Yii::t('system', 'Search'), [
                'class' => 'btn btn-primary',
            ]);
            echo Html::endForm();
            echo html::endTag('div');
        }
    }
    
    /**
     * @desc render tool bar
     */
    public function renderToolBar()
    {
        if (is_array($this->toolBars) && !empty($this->toolBars))
        {
            echo Html::beginTag('div', [
                'id' => 'toolbar',
                'class' => 'btn-group',
            ]);
            foreach ($this->toolBars as $toolBars)
            {
                $href = isset($toolBars['href']) ? trim($toolBars['href']) : '';
                if(!self::_aclcheck(Yii::$app->user->identity->id,$href))
                    continue;
                $text = isset($toolBars['text']) ? trim($toolBars['text']) : '';
                $buttonType = isset($toolBars['buttonType']) ? trim($toolBars['buttonType']) : '';
                $htmlOptions = isset($toolBars['htmlOptions']) ? $toolBars['htmlOptions'] : [];
                $htmlOptions['class'] = isset($htmlOptions['class']) ? $htmlOptions['class'] . ' btn btn-default': 'btn btn-default';
                $iconClassName = '';
                switch ($buttonType)
                {
                    case 'add':
                        $iconClassName = 'glyphicon glyphicon-plus';
                        $text || $text = Yii::t('system', 'Button Add');
                        break;
                    case 'delete':
                        $iconClassName = 'glyphicon glyphicon-remove';
                        $text || $text = Yii::t('system', 'Button Delete');
                        break;
                    case 'edit':
                        $iconClassName = 'glyphicon glyphicon-pencil';
                        $text || $text = Yii::t('system', 'Button Edit');
                        break;
                    default:
                        $iconClassName = 'glyphicon ';                      
                }
                $content = Html::tag('span', $text, ['class' => $iconClassName]);
                echo Html::a($content, $href, $htmlOptions);               
            }
            echo Html::endTag('div');
        }
    }

    public static function _aclcheck($userId,$href)
    {
        if($href == '#')
            return true;
        $href = strpos($href,'?') != false ? substr($href,0,strpos($href,'?')) : $href;
        $href = trim($href,'/');
        $routes = explode('/', $href);
        if(count($routes) < 3)
            return false;
        $parentModule = isset($routes[0]) ? $routes[0] : '';
        //过滤忽略认证的模块或者路由
        $authIgnores = isset(\Yii::$app->params['authIgnores']) ? \Yii::$app->params['authIgnores'] : [];
        $ignoreModules = isset($authIgnores['modules']) ? (array)$authIgnores['modules'] : [];
        $ignoreRoutes = isset($authIgnores['routes']) ? (array)$authIgnores['routes'] : [];
        if (\Yii::$app->user->getIsGuest() && (in_array($parentModule, $ignoreModules) || in_array($href, $ignoreRoutes)))
        {
            return true;
        }
        else
        {
            $session = \Yii::$app->session;
            $aclSession = $session->get('_acl');
            $roleKey = AclManage::ROLE_KEY_PREFIX . $userId;
            if (empty($aclSession) || !isset($aclSession[$roleKey]))
                return false;
            $acl = $aclSession[$roleKey];
            $moduleName = $routes[0];
            $controllerName =  $routes[1];
            $actionName =  $routes[2];
            $resourceName = ucfirst($moduleName) . '_' . ucfirst($controllerName) . 'Controller' . '_' . 'action' . ucfirst($actionName);
            if ($acl->checkAccess($resourceName, null))
                return true;
            else
                return false;
        }
    }
    
    /**
     * Renders the table body.
     * @return string the rendering result.
     */
    public function renderTableBody()
    {
        $models = array_values($this->dataProvider->getModels());
        $keys = $this->dataProvider->getKeys();
        $rows = [];
        foreach ($models as $index => $model) {
            $key = $keys[$index];
            if ($this->beforeRow !== null) {
                $row = call_user_func($this->beforeRow, $model, $key, $index, $this);
                if (!empty($row)) {
                    $rows[] = $row;
                }
            }
    
            $rows[] = $this->renderTableRow($model, $key, $index);
    
            if ($this->afterRow !== null) {
                $row = call_user_func($this->afterRow, $model, $key, $index, $this);
                if (!empty($row)) {
                    $rows[] = $row;
                }
            }
        }
    
        if (empty($rows)) {
            $colspan = count($this->columns);
    
            return "<tbody>\n<tr><td colspan=\"$colspan\">" . $this->renderEmpty() . "</td></tr>\n</tbody>";
        } else {
            return "<tbody>\n" . implode("\n", $rows) . "\n</tbody>";
        }
    }
    
    /**
     * Renders a table row with the given data model and key.
     * @param mixed $model the data model to be rendered
     * @param mixed $key the key associated with the data model
     * @param int $index the zero-based index of the data model among the model array returned by [[dataProvider]].
     * @return string the rendering result
     */
    public function renderTableRow($model, $key, $index)
    {
        $cells = [];
        /* @var $column Column */
        foreach ($this->columns as $column) {
            $cells[] = $column->renderDataCell($model, $key, $index);
        }
        if ($this->rowOptions instanceof Closure) {
            $options = call_user_func($this->rowOptions, $model, $key, $index, $this);
        } else {
            $options = $this->rowOptions;
        }
        $options['data-key'] = is_array($key) ? json_encode($key) : (string) $key;
    
        return Html::tag('tr', implode('', $cells), $options);
    }
    
    /**
     * Creates column objects and initializes them.
     */
    protected function initColumns()
    {
        if (empty($this->columns)) {
            $this->guessColumns();
        }
        foreach ($this->columns as $i => $column) {
            if (is_string($column)) {
                $column = $this->createDataColumn($column);
            } else {
                $column = Yii::createObject(array_merge([
                    'class' => $this->dataColumnClass ? : \yii\grid\DataColumn::className(),
                    'grid' => $this,
                ], $column));
            }
            if (!$column->visible) {
                unset($this->columns[$i]);
                continue;
            }
            $this->columns[$i] = $column;
        }
    }
    
    /**
     * Creates a [[DataColumn]] object based on a string in the format of "attribute:format:label".
     * @param string $text the column specification string
     * @return DataColumn the column instance
     * @throws InvalidConfigException if the column specification is invalid
     */
    protected function createDataColumn($text)
    {
        if (!preg_match('/^([^:]+)(:(\w*))?(:(.*))?$/', $text, $matches)) {
            throw new InvalidConfigException('The column must be specified in the format of "attribute", "attribute:format" or "attribute:format:label"');
        }
    
        return Yii::createObject([
            'class' => $this->dataColumnClass ? : \yii\grid\DataColumn::className(),
            'grid' => $this,
            'attribute' => $matches[1],
            'format' => isset($matches[3]) ? $matches[3] : 'text',
            'label' => isset($matches[5]) ? $matches[5] : null,
        ]);
    }
    
    /**
     * This function tries to guess the columns to show from the given data
     * if [[columns]] are not explicitly specified.
     */
    protected function guessColumns()
    {
        $models = $this->dataProvider->getModels();
        $model = reset($models);
        if (is_array($model) || is_object($model)) {
            foreach ($model as $name => $value) {
                if ($value === null || is_scalar($value) || is_callable([$value, '__toString'])) {
                    $this->columns[] = (string) $name;
                }
            }
        }
    }
    
    /**
     * @desc render tag
     */
    public function renderTags()
    {
        $tags = $this->tags;
        $is_tags = $this->is_tags;

        if($is_tags == true)
        {
            echo Html::beginTag('div', ['class' => 'alert alert-danger', 'id' => 'tag-box']);
            echo Html::beginTag('ul', ['class' => 'list-inline']);
            echo Html::tag('li', '<strong>' . \Yii::t('system', 'Custom Tag') . '</strong>');
            echo Html::tag('li', Html::tag('a', \Yii::t('system', 'All'), [
                'href' => 'javascript:void(0);',
                'onclick' => 'queryByTag(this, "")',
                'class' => 'tag-label label-off',
            ]));
            if (!empty($tags))
            {
                foreach ($tags as $row)
                {
                    echo Html::tag('li', Html::tag('a', $row['name'] . '(' . $row['count'] . ')', [
                        'href' => 'javascript:void(0);',
                        'onclick' => 'queryByTag(this, ' . $row['id'] . ')',
                        'class' => 'tag-label label-off',
                    ]));
                }
            }
            echo Html::endTag('ul');
            echo Html::endTag('div');
        }
    }


    public function renderSites()
    {
        $sites = $this->sites;
        $is_sites = $this->is_sites;

        if($is_sites == true)
        {
            echo Html::beginTag('div', ['class' => 'alert alert-danger', 'id' => 'site-box']);
            echo Html::beginTag('ul', ['class' => 'list-inline']);
            echo Html::tag('li', '<strong>' . '站点：' . '</strong>');
            echo Html::tag('li', Html::tag('a', \Yii::t('system', 'All'), [
                'href' => 'javascript:void(0);',
                'onclick' => 'queryBySite(this, "")',
                'class' => 'site-label label-off',
            ]));
            if (!empty($sites))
            {
                foreach ($sites as $row)
                {
                    echo Html::tag('li', Html::tag('a', $row['name'] . '(' . $row['count'] . ')', [
                        'href' => 'javascript:void(0);',
                        'onclick' => 'queryBySite(this, ' . $row['id'] . ')',
                        'class' => 'site-label label-off',
                    ]));
                }
            }
            echo Html::endTag('ul');
            echo Html::endTag('div');
        }
    }

    /**
     * @desc render tag
     */
    public function renderAccountEmail()
    {
        $account_email = $this->account_email;
        if (!empty($account_email))
        {
            echo Html::beginTag('div', ['class' => 'alert alert-danger', 'id' => 'account_email-box']);
            echo Html::beginTag('ul', ['class' => 'list-inline']);
            echo Html::tag('li', '<strong>' . '帐号：' . '</strong>');
            echo Html::tag('li', Html::tag('a', \Yii::t('system', 'All'), [
                'href' => 'javascript:void(0);',
                'onclick' => 'queryByAccount(this, "")',
                'class' => 'account-label label-off',
            ]));
            foreach ($account_email as $row)
            {
                echo Html::tag('li', Html::tag('a', $row['name'] . '(' . $row['count'] . ')', [
                    'href' => 'javascript:void(0);',
                    'onclick' => 'queryByAccount(this, ' . $row['id'] . ')',
                    'class' => 'account-label label-off',
                ]));
            }
            echo Html::endTag('ul');
            echo Html::endTag('div');
        }
    }

    public function renderHeadSummary()
    {
        if(!empty($this->headSummary['data']))
        {
            echo '<style>#headSummary-box{cursor: pointer} .db_click_hidden_over_flow{overflow: hidden;white-space: nowrap;text-overflow: ellipsis;}</style>';
            echo '<script type="application/javascript">$(function(){$("#headSummary-box").dblclick(function(){if($(this).hasClass("db_click_hidden_over_flow")){$(this).removeClass("db_click_hidden_over_flow");}else{$(this).addClass("db_click_hidden_over_flow");}})});</script>';
            echo Html::beginTag('div', ['class' => 'alert alert-warning', 'id' => 'headSummary-box']);
            echo Html::tag('input','',['type'=>'hidden','name'=>$this->headSummary['name'],'id'=>'headSummary']);
            echo Html::beginTag('ul', ['class' => 'list-inline']);
            echo Html::tag('li', '<strong>' . $this->headSummary['label'] . '</strong>');
            echo Html::tag('li', Html::tag('a', \Yii::t('system', 'All'), [
                'href' => 'javascript:void(0);',
                'onclick' => 'queryByTag(this, "","headSummary")',
                'class' => 'tag-label label-off',
            ]));
            foreach($this->headSummary['data'] as $accountId=>$data)
            {
                echo Html::tag('li', Html::tag('a', $data, [
                    'href' => 'javascript:void(0);',
                    'onclick' => 'queryByTag(this, ' . $accountId . ',"headSummary")',
                    'class' => 'tag-label label-off',
                ]));
            }
            echo Html::endTag('ul');
            echo Html::endTag('div');
        }
    }
}