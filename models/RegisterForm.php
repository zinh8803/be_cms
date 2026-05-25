<?php

namespace app\models;

use Yii;
use yii\base\Model;

/**
 * RegisterForm is the model behind the user registration form.
 */
class RegisterForm extends Model
{
    public $username;
    public $email;
    public $password;

    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
            [['username', 'email', 'password'], 'required', 'message' => '{attribute} không được để trống.'],
            ['username', 'string', 'min' => 3, 'max' => 50, 'message' => 'Tên tài khoản phải chứa từ 3 đến 50 ký tự.'],
            ['email', 'email', 'message' => 'Địa chỉ email không hợp lệ.'],
            ['email', 'unique', 'targetClass' => User::class, 'message' => 'Địa chỉ email này đã được sử dụng.'],
            ['username', 'unique', 'targetClass' => User::class, 'message' => 'Tên tài khoản này đã được sử dụng.'],
            ['password', 'string', 'min' => 6, 'message' => 'Mật khẩu phải chứa ít nhất 6 ký tự.'],
        ];
    }

    /**
     * @return array customized attribute labels
     */
    public function attributeLabels()
    {
        return [
            'username' => 'Tên tài khoản',
            'email' => 'Địa chỉ Email',
            'password' => 'Mật khẩu',
        ];
    }
}
