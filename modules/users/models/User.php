<?php
/**
 * @desc user model
 * @author Fun
 */

namespace app\modules\users\models;

use app\components\Model;
use app\modules\systems\models\ErpSystemApi;
use app\modules\users\models\Role;

class User extends Model implements \yii\web\IdentityInterface
{
    const STATUS_VALID = 1;
    const STATUS_INVALID = 0;

    /**
     * @desc confirm password
     * @var unknown
     */
    public $confirm_password;

    const LOGIN_STATUS_ON = 1;        //已登录
    const LOGIN_STATUS_OFF = 0;        //未登录

    /**
     * @desc set table name
     * @return string
     */
    public static function tableName()
    {
        return '{{%user}}';
    }

    public function getRole()
    {
        return $this->hasOne(Role::className(), ['id' => 'role_id']);
    }

    /**
     * !CodeTemplates.overridecomment.nonjd!
     * @see \yii\db\ActiveRecord::attributes()
     */
    public function attributes()
    {
        $attributes        = parent::attributes();
        $extraAttributes[] = 'status_text';                 //状态
        $extraAttributes[] = 'role_name';                   //角色名
        $extraAttributes[] = 'role_ids';                    //角色IDs
        $extraAttributes[] = 'roles';                   //角色CODEs
        return array_merge($attributes, $extraAttributes);
    }

    /**
     * !CodeTemplates.overridecomment.nonjd!
     * @see \yii\base\Model::rules()
     */
    public function rules()
    {
        return [
            [['user_name', 'login_name', 'status'], 'required'],
            [['login_password', 'confirm_password'], 'required', 'on' => 'add'],
            [['login_password', 'confirm_password'], function ($attribute, $params) {
                if (!preg_match('/[0-9a-z-A-Z~!@#$%^&*]/', $this->$attribute))
                    $this->addError($attribute, 'Invalid Password');
                if ($this->login_password != $this->confirm_password)
                    $this->addError($attribute, 'Confirm Password Must The Same As Password');
                if (strlen($this->$attribute) < 6 || strlen($this->$attribute) >= 50)
                    $this->addError($attribute, 'Passwrod Length Must Between 6 and 50');
            }, 'on' => 'add'],
            ['login_name', function ($attribute, $params) {
                if ($this->findOne(['login_name' => $this->$attribute]))
                    $this->addError($attribute, 'Login Name Has Exists');
            }, 'on' => 'add'],
            [['user_telephone', 'token', 'expire_time'], 'safe'],
            ['user_email', 'email'],
            //['role_id', 'integer']
        ];
    }

    public function filterOptions()
    {
        return [
            [
                'name'   => 'login_name',
                'type'   => 'text',
                'search' => 'FULL LIKE',
            ],
            [
                'name'   => 'user_name',
                'type'   => 'text',
                'search' => 'FULL LIKE',
            ],
            [
                'name'   => 'user_number',
                'type'   => 'text',
                'search' => '=',
            ],
        ];
    }

    /**
     * @desc find user by id
     * @param unknown $id
     * @return \yii\db\static
     */
    public static function findIdentity($id)
    {
        $userModel = parent::findOne($id);
        if (!empty($userModel)) {
            $roleInfos = UserRole::getUserRoles($userModel->id);
            $roles     = [];
            if (!empty($roleInfos)) {
                foreach ($roleInfos as $roleInfo)
                    $roles[$roleInfo['role_id']] = $roleInfo['role_code'];
            }
            $userModel->roles = $roles;
        }
        return $userModel;
    }

    /**
     *
     * @param unknown $token
     * @param string $type
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        return true;
    }

    /**
     * !CodeTemplates.overridecomment.nonjd!
     * @see \yii\web\IdentityInterface::getId()
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * !CodeTemplates.overridecomment.nonjd!
     * @see \yii\web\IdentityInterface::getAuthKey()
     */
    public function getAuthKey()
    {
        return true;
    }

    /**
     * !CodeTemplates.overridecomment.nonjd!
     * @see \yii\web\IdentityInterface::validateAuthKey()
     */
    public function validateAuthKey($authKey)
    {
        return true;
    }

    /**
     * @desc search list
     * @param unknown $params
     * @param string $query
     */
    public function searchList($params = [])
    {
        $sort               = new \yii\data\Sort();
        $sort->defaultOrder = array(
            'id' => SORT_ASC
        );
        $query              = self::find();
        $query->with('role');
        $dataProvider = parent::search($query, $sort, $params);
        $models       = $dataProvider->getModels();
        foreach ($models as $key => $model) {
            $roleName = UserRole::getUserRoleName($model->id);
            $models[$key]->setAttribute('status_text', self::getStatusList($model->status));
            $models[$key]->setAttribute('role_name', $roleName);
        }
        $dataProvider->setModels($models);
        return $dataProvider;
    }

    /**
     * !CodeTemplates.overridecomment.nonjd!
     * @see \yii\base\Model::attributeLabels()
     */
    public function attributeLabels()
    {
        return [
            'user_name'        => \Yii::t('user', 'User Name'),
            'login_name'       => \Yii::t('user', 'Login Name'),
            'user_email'       => \Yii::t('user', 'User Email'),
            'user_telephone'   => \Yii::t('user', 'Telephone'),
            'status'           => \Yii::t('system', 'Status'),
            'login_password'   => \Yii::t('user', 'Password'),
            'last_login_time'  => \Yii::t('user', 'Last Login Time'),
            'confirm_password' => \Yii::t('user', 'Confirm Password'),
            'login_status'     => \Yii::t('user', 'Login Status'),
            'role_name'        => \Yii::t('user', 'Role Name'),
            'role_ids'         => \Yii::t('user', 'Roles'),
            'create_by'        => \Yii::t('system', 'Create By'),
            'create_time'      => \Yii::t('system', 'Create Time'),
            'modify_by'        => \Yii::t('system', 'Modify By'),
            'modify_time'      => \Yii::t('system', 'Modify Time'),
            'status_text'      => \Yii::t('system', 'Status Text'),
            'user_number'      => '用户工号'
        ];
    }

    /**
     * @desc 用户登录
     * @param unknown $loginName
     * @param unknown $password
     * @return multitype:boolean \yii\string |boolean
     */
    public function login($loginName, $password)
    {
        $userInfo = $this->findOne(['login_name' => $loginName, 'status' => self::STATUS_VALID]);
        //if (empty($userInfo) || !\Yii::$app->getSecurity()->validatePassword($password, $userInfo->login_password))
        if (empty($userInfo))
            return [
                false, \Yii::t('user', 'Login Name Or Password Error')
            ];
        //调用ERP接口登录获取token
        $systemApi = new ErpSystemApi();
        $data      = ['username' => $loginName, 'password' => $password];
        $response  = $systemApi->login($data);
        if (empty($response)) {
            return [
                false, $systemApi->getExcptionMessage(),
            ];
        }
        $userInfo->token       = $response->token;
        $userInfo->expire_time = $response->expireTime;
        $flag                  = $userInfo->save(false, ['token', 'expire_time']);
        if (!$flag)
            return [false, 'Update Failed'];
        //if ()

        /*         if ($userInfo->login_status == self::LOGIN_STATUS_ON)
                    return [
                        false, \Yii::t('user', 'The User Had Logined')
                    ]; */

        //认证成功，将用户注册到\yii\web\User组件，更新登录时间和登录状态
        $userInfo->last_login_time = date('Y-m-d H:i:s');
        $userInfo->login_status    = self::LOGIN_STATUS_ON;
        if (!$userInfo->save(false, ['last_login_time', 'login_status']))
            return [
                false, \Yii::t('user', 'Login Failed')
            ];
        $roleInfos = UserRole::getUserRoles($userInfo->id);
        $roles     = [];
        if (!empty($roleInfos)) {
            foreach ($roleInfos as $roleInfo)
                $roles[$roleInfo['role_id']] = $roleInfo['role_code'];
        }
        $userInfo->roles = $roles;
        \Yii::$app->user->login($userInfo, 3600);
        return true;
    }

    public static function getAllAccounts($status = null)
    {
        $condition = [];
        if (!is_null($status))
            $condition = ['status' => (int)$status];
        return self::findAll($condition);
    }

    public static function getIdNamePairs()
    {
        $list = ['' => '所有'];
        $res  = self::getAllAccounts(self::STATUS_VALID);
        if (!empty($res)) {
            foreach ($res as $row) {
                $list[$row->id] = $row['user_name'];
            }
        }
        return $list;
    }

    /**
     * 根据当前登录用户的角色id 获取子角色id
     * @param type $roleId
     * @param type $status
     * @return type
     * @author allen <2018-07-19>
     */
    public static function getUserInfoByRole($roleId, $status = 1)
    {
        $query = User::find();
        $query->select(['id', 'user_name']);
        if ($roleId != 1) {
            //获取对应角色下的用户列表[包含下级]
            $roleids = Role::getChildRoleIds($roleId);
            $query->where(['in', 'role_id', $roleids]);
        }
        $query->andWhere(['status' => $status]);
        return $query->asArray()->all();
    }

    public static function getUsername($id) {

        if (empty($id)) return '';
        $data = self::getIdNamePairs();

        if (! isset($data[$id]) ) {
            return '';
        }else{
            return $data[$id];
        }
    }
}
