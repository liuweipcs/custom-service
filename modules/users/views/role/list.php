<?php 
use yii\helpers\Url;

$this->title = '角色管理';
function RecursiveRoleTree($datas)
{
    foreach ($datas as $row)
    {
        $children = isset($row['children']) ? $row['children'] : [];
        $level = $row['level'];
        echo '<tr>
                <td>' . str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', ($level - 1)) . $row['role_name'] . '</td>
                <td>' . $row['role_code'] . '</td>
                <td>' . ($row['status'] ? Yii::t('system', 'Yes') : Yii::t('system', 'No')) . '</td>
                <td>' . $level . '</td>
                <td>' . $row['description'] . '</td>
                <td>
                    <div class="btn-group btn-list">
                        <button type="button" class="btn btn-default btn-sm">' . Yii::t('system', 'Operation') . '</button>
                        <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown">
                            <span class="caret"></span>
                            <span class="sr-only">' . Yii::t('system', 'Toggle Dropdown List') . '</span>
                        </button>
                    <ul class="dropdown-menu" rol="menu">
                        <li><a href="' . Url::toRoute(['/users/role/setpower', 'id' => $row['id']]) . '">' . Yii::t('system', 'Set Privileges') . '</a></li>
                        <li><a class="add-button" href="' . Url::toRoute(['/users/role/edit', 'id' => $row['id']]) . '">' . Yii::t('system', 'Edit') . '</a></li>
                        <!--<li><a class="delete-button" href="' . Url::toRoute(['/users/role/delete', 'id' => $row['id']]) . '">' . Yii::t('system', 'Delete') . '</a></li>-->
                    </ul>
                </td>
            </tr>';
        if (!empty($children))
        {
            RecursiveRoleTree($children);
        }
    }
}
?>
<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <div>
                <a _width="48%" _height="48%" class="btn btn-primary add-button" href="<?php echo url::toRoute('/users/role/add');?>">
                    <span class="glyphicon glyphicon-plus"><?php echo Yii::t('role', 'Add New Role');?></span>
                </a>
            </div>
            <table class="table">
                <thead>
                    <tr>
                        <th width="20%"><?php echo Yii::t('role', 'Role Name');?></th>
                        <th width="30%"><?php echo Yii::t('role', 'Role Code');?></th>
                        <th width="10%"><?php echo Yii::t('systems', 'Status');?></th>
                        <th width="10%"><?php echo Yii::t('role', 'Role Level');?></th>
                        <th width="15%"><?php echo Yii::t('role', 'Description');?></th>
                        <th width="15%"><?php echo Yii::t('system', 'Operation');?></th>
                    </tr>
                </thead>
                <tbody>
                <?php 
                    RecursiveRoleTree($roleTree);
                ?>
                    <tr>
                        <td colspan="7"></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>