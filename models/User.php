<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;
use yii\behaviors\TimestampBehavior;

/**
 * User model representing the "user" table.
 *
 * @property int $id
 * @property string $username
 * @property string $email
 * @property string $password_hash
 * @property string $auth_key
 * @property string|null $access_token
 * @property int $status
 * @property int $created_at
 * @property int $updated_at
 */
class User extends ActiveRecord implements IdentityInterface
{
    const STATUS_ACTIVE = 10;
    const STATUS_INACTIVE = 0;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%user}}';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            TimestampBehavior::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['username', 'email', 'password_hash', 'auth_key'], 'required'],
            ['status', 'default', 'value' => self::STATUS_ACTIVE],
            ['status', 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_INACTIVE]],
            [['username', 'email'], 'string', 'max' => 255],
            [['username', 'email'], 'unique'],
            ['email', 'email'],
            ['access_token', 'string', 'max' => 255],
            ['access_token', 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function findIdentity($id)
    {
        return static::findOne(['id' => $id, 'status' => self::STATUS_ACTIVE]);
    }

    /**
     * {@inheritdoc}
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        // Check if it's a JWT (JWTs have 3 parts separated by dots)
        if (substr_count($token, '.') === 2) {
            try {
                $secret = $_ENV['JWT_SECRET'] ?? getenv('JWT_SECRET') ?: Yii::$app->params['jwt.secret'] ?? Yii::$app->request->cookieValidationKey;
                $decoded = \Firebase\JWT\JWT::decode($token, new \Firebase\JWT\Key($secret, 'HS256'));
                
                // Return active user
                return static::findOne(['id' => $decoded->sub, 'status' => self::STATUS_ACTIVE]);
            } catch (\Exception $e) {
                return null; // Invalid token or expired
            }
        }

        // Fallback for legacy or seed tokens (non-JWT)
        $parts = explode('_', $token);
        if (count($parts) >= 2) {
            $timestamp = (int) end($parts);
            // Dynamic tokens generated via generateAccessToken() contain a 10-digit timestamp.
            // Seed/legacy tokens (like "admin_token_123") have small numbers and won't be checked for expiration.
            if ($timestamp > 1000000000) {
                $expire = Yii::$app->params['user.accessTokenExpire'] ?? 900;
                if ($timestamp + $expire < time()) {
                    return null; // Token expired
                }
            }
        }
        return static::findOne(['access_token' => $token, 'status' => self::STATUS_ACTIVE]);
    }

    /**
     * Finds user by username.
     *
     * @param string $username
     * @return static|null
     */
    public static function findByUsername($username)
    {
        return static::findOne(['username' => $username, 'status' => self::STATUS_ACTIVE]);
    }

    /**
     * Finds user by email.
     *
     * @param string $email
     * @return static|null
     */
    public static function findByEmail($email)
    {
        return static::findOne(['email' => $email, 'status' => self::STATUS_ACTIVE]);
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->getPrimaryKey();
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthKey()
    {
        return $this->auth_key;
    }

    /**
     * {@inheritdoc}
     */
    public function validateAuthKey($authKey)
    {
        return $this->getAuthKey() === $authKey;
    }

    /**
     * Validates password.
     *
     * @param string $password password to validate
     * @return bool if password provided is valid for current user
     */
    public function validatePassword($password)
    {
        return Yii::$app->security->validatePassword($password, $this->password_hash);
    }

    /**
     * Generates password hash from password and sets it to the model.
     *
     * @param string $password
     */
    public function setPassword($password)
    {
        $this->password_hash = Yii::$app->security->generatePasswordHash($password);
    }

    /**
     * Generates "remember me" authentication key.
     */
    public function generateAuthKey()
    {
        $this->auth_key = Yii::$app->security->generateRandomString();
    }

    /**
     * Generates new access token.
     */
    public function generateAccessToken()
    {
        $secret = $_ENV['JWT_SECRET'] ?? getenv('JWT_SECRET') ?: Yii::$app->params['jwt.secret'] ?? Yii::$app->request->cookieValidationKey;
        $expire = Yii::$app->params['user.accessTokenExpire'] ?? 900;
        
        $payload = [
            'sub' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'iat' => time(),
            'exp' => time() + $expire,
        ];
        
        $this->access_token = \Firebase\JWT\JWT::encode($payload, $secret, 'HS256');
    }
}

