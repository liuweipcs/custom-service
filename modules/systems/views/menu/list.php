<?php 
use yii\helpers\Url;

$this->title = '菜单管理';
function menuTree($datas)
{
    foreach ($datas as $row)
    {
        $children = isset($row['children']) ? $row['children'] : [];
        $level = $row['level'];
        echo '<tr>
                <td>' . str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', ($level - 1)) . $row['menu_name'] . '</td>
                <td>' . $row['route'] . '</td>
                <td>' . ($row['is_show'] ? Yii::t('system', 'Yes') : Yii::t('system', 'No')) . '</td>
                <td>' . $level . '</td>
                <td>' . ($row['is_new'] ? Yii::t('system', 'Yes') : Yii::t('system', 'No')) . '</td>
                <td>' . $row['sort_order'] . '</td>
                <td>
                    <div class="btn-group btn-list">
                        <button type="button" class="btn btn-default btn-sm">' . Yii::t('system', 'Operation') . '</button>
                        <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown">
                            <span class="caret"></span>
                            <span class="sr-only">' . Yii::t('system', 'Toggle Dropdown List') . '</span>
                        </button>
                    <ul class="dropdown-menu" rol="menu">';
        if(\app\components\GridView::_aclcheck(Yii::$app->user->identity->id,'/systems/menu/setresource'))
             echo '<li><a class="edit-button" href="' . Url::toRoute(['/systems/menu/setresource', 'id' => $row['id']]) . '">' . Yii::t('menu', 'Set Resource') . '</a></li>';
        if(\app\components\GridView::_aclcheck(Yii::$app->user->identity->id,'/systems/menu/edit'))
            echo '<li><a class="edit-button" href="' . Url::toRoute(['/systems/menu/edit', 'id' => $row['id']]) . '">' . Yii::t('system', 'Edit') . '</a></li>';
        if(\app\components\GridView::_aclcheck(Yii::$app->user->identity->id,'/systems/menu/delete'))
            echo '<li><a class="delete-button" href="' . Url::toRoute(['/systems/menu/delete', 'id' => $row['id']]) . '">' . Yii::t('system', 'Delete') . '</a></li>';
        echo '</ul>
                </td>
            </tr>';
        if (!empty($children))
        {
            menuTree($children);
        }
    }
}
?>
<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <div>
                <?php if(\app\components\GridView::_aclcheck(Yii::$app->user->identity->id,'/systems/menu/add')):?>
                <a _width="48%" _height="48%" class="btn btn-primary add-button" href="<?php echo url::toRoute('/systems/menu/add');?>">
                    <span class="glyphicon glyphicon-plus"><?php echo Yii::t('menu', 'Add New Menu');?></span>
                </a>
                <?php endif;?>
            </div>
            <table class="table">
                <thead>
                    <tr>
                        <th width="20%"><?php echo Yii::t('menu', 'Menu Name');?></th>
                        <th width="30%"><?php echo Yii::t('menu', 'Route Addr');?></th>
                        <th width="8%"><?php echo Yii::t('menu', 'Is Show');?></th>
                        <th width="8%"><?php echo Yii::t('menu', 'Menu Level');?></th>
                        <th width="13%"><?php echo Yii::t('menu', 'Open On New Window');?></th>
                        <th width="8%"><?php echo Yii::t('menu', 'Sort Order');?></th>
                        <th width="13%"><?php echo Yii::t('system', 'Operation');?></th>
                    </tr>
                </thead>
                <tbody>
                <?php 
                    menuTree($menuTree);
                ?>
                    <tr>
                        <td colspan="7"></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>