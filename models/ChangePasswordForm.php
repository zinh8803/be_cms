<?php

namespace app\models;

use Yii;
use yii\base\Model;

/**
 * ChangePasswordForm is the model behind the password update form.
 */
class ChangePasswordForm extends Model
{
    public $oldPassword;
    public $newPassword;
    public $confirmPassword;

    private $_user;

    /**
     * @param User $user
     * @param array $config
     */
    public function __construct(User $user, $config = [])
    {
        $this->_user = $user;
        parent::__construct($config);
    }

    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
            [['oldPassword', 'newPassword', 'confirmPassword'], 'required', 'message' => '{attribute} không được để trống.'],
            ['oldPassword', 'validateOldPassword'],
            ['newPassword', 'string', 'min' => 6, 'message' => 'Mật khẩu mới phải chứa ít nhất 6 ký tự.'],
            ['confirmPassword', 'compare', 'compareAttribute' => 'newPassword', 'message' => 'Mật khẩu xác nhận không khớp.'],
        ];
    }

    /**
     * Validates the old password.
     */
    public function validateOldPassword($attribute, $params)
    {
        if (!$this->hasErrors()) {
            if (!$this->_user->validatePassword($this->oldPassword)) {
                $this->addError($attribute, 'Mật khẩu hiện tại không chính xác.');
            }
        }
    }

    /**
     * @return array customized attribute labels
     */
    public function attributeLabels()
    {
        return [
            'oldPassword' => 'Mật khẩu hiện tại',
            'newPassword' => 'Mật khẩu mới',
            'confirmPassword' => 'Xác nhận mật khẩu mới',
        ];
    }

    /**
     * Changes password.
     * @return bool
     */
    public function change()
    {
        if ($this->validate()) {
            $this->_user->setPassword($this->newPassword);
            return $this->_user->save(false);
        }
        return false;
    }
}
