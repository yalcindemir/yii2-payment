<?php
/**
 * @link http://www.tintsoft.com/
 * @copyright Copyright (c) 2012 TintSoft Technology Co. Ltd.
 * @license http://www.tintsoft.com/license/
 */

namespace yuncms\payment\models;

use Yii;
use yii\behaviors\AttributeBehavior;
use yii\db\Query;
use yii\db\ActiveRecord;
use yii\db\BaseActiveRecord;
use yii\behaviors\TimestampBehavior;
use yuncms\payment\ModuleTrait;
use yuncms\user\models\User;

/**
 * Payment ActiveRecord model
 *
 * Database fields:
 * @property integer $id 付款ID
 * @property integer $model_id 订单ID
 * @property string $model 订单模型
 * @property integer $user_id 用户ID
 * @property string $gateway 支付网关
 * @property string $pay_id 支付号
 * @property string $name 付款事由
 * @property integer $trade_type 交易类型
 * @property integer $trade_state 交易状态
 * @property integer $currency 币种
 * @property integer $money 签署
 * @property integer $created_at 创建时间
 * @property integer $updated_at 更新时间
 * @property string $note 备注
 * @property string $return_url 支付后的跳转URL
 * @property string $ip 用户IP
 * @package yuncms\payment
 */
class Payment extends ActiveRecord
{
    use ModuleTrait;

    //交易类型
    const TYPE_NATIVE = 0b1;//原生扫码支付
    const TYPE_JS_API = 0b10;//应用内JS API,如微信
    const TYPE_APP = 0b11;//app支付
    const TYPE_MWEB = 0b100;//H5支付
    const TYPE_MICROPAY = 0b101;//刷卡支付
    const TYPE_OFFLINE = 0b110;//离线（汇款、转账等）支付

    //交易状态
    const STATE_NOT_PAY = 0b0;//未支付
    const STATE_SUCCESS = 0b1;//支付成功
    const STATE_FAILED = 0b10;//支付失败
    const STATE_REFUND = 0b11;//转入退款
    const STATE_CLOSED = 0b100;//已关闭
    const STATE_REVOKED = 0b101;//已撤销
    const STATE_ERROR = 0b110;//错误

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%payment}}';
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            TimestampBehavior::className(),
            [
                'class' => 'yii\behaviors\BlameableBehavior',
                'attributes' => [
                    BaseActiveRecord::EVENT_BEFORE_INSERT => ['user_id'],
                ],
            ],
            [
                'class' => AttributeBehavior::className(),
                'attributes' => [
                        ActiveRecord::EVENT_BEFORE_INSERT => 'id',
                ],
                'value' => function ($event) {
                    return $this->generatePaymentId();
                }
            ],
            [
                'class' => AttributeBehavior::className(),
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => 'ip',
                ],
                'value' => function ($event) {
                    return Yii::$app->request->userIP;
                }
            ],
        ];
    }

    /** @inheritdoc */
    public function rules()
    {
        return [
            [['currency', 'trade_type', 'money'], 'required'],
            ['gateway', 'string', 'max' => 50],
            ['id', 'unique', 'message' => Yii::t('payment', 'This id has already been taken')],
            ['model_id', 'integer'],
            ['model', 'string', 'max' => 255],

            ['trade_type', 'default', 'value' => static::TYPE_NATIVE],
            ['trade_type', 'in', 'range' => [
                static::TYPE_NATIVE,//扫码付款
                static::TYPE_JS_API,//嵌入式 JS SDK付款
                static::TYPE_APP,//APP付款
                static::TYPE_MWEB,//H5 Web 付款
                static::TYPE_MICROPAY,//刷卡付款
                static::TYPE_OFFLINE,//转账汇款
            ]],

            ['trade_state', 'default', 'value' => static::STATE_NOT_PAY],
            ['trade_state', 'in', 'range' => [
                static::STATE_NOT_PAY,
                static::STATE_SUCCESS,
                static::STATE_FAILED,
                static::STATE_REFUND,
                static::STATE_CLOSED,
                static::STATE_REVOKED,
                static::STATE_ERROR,
            ]],
        ];
    }

    /** @inheritdoc */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('payment', 'ID'),
            'model_id' => Yii::t('payment', 'Model ID'),
            'model' => Yii::t('payment', 'Model'),
            'pay_id' => Yii::t('payment', 'Pay ID'),
            'user_id' => Yii::t('payment', 'User ID'),
            'name' => Yii::t('payment', 'Payment Name'),
            'gateway' => Yii::t('payment', 'Payment Gateway'),
            'currency' => Yii::t('payment', 'Currency'),
            'money' => Yii::t('payment', 'Money'),
            'trade_type' => Yii::t('payment', 'Trade Type'),
            'trade_state' => Yii::t('payment', 'Trade State'),
            'ip' => Yii::t('payment', 'Pay IP'),
            'note' => Yii::t('payment', 'Pay Note'),
            'created_at' => Yii::t('payment', 'Created At'),
            'updated_at' => Yii::t('payment', 'Updated At'),
        ];
    }

    /**
     * User Relation
     * @return \yii\db\ActiveQueryInterface
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
    }

    /**
     * 生成付款流水号
     */
    public function generatePaymentId()
    {
        $i = rand(0, 9999);
        do {
            if (9999 == $i) {
                $i = 0;
            }
            $i++;
            $id = time() . str_pad($i, 4, '0', STR_PAD_LEFT);
            $row = (new Query())->from(static::tableName())->where(['id' => $id])->exists();
        } while ($row);
        return $id;
    }

//    /**
//     * 保存前
//     * @param bool $insert
//     * @return bool
//     */
//    public function beforeSave($insert)
//    {
//        if (parent::beforeSave($insert)) {
//            return true;
//        } else {
//            return false;
//        }
//    }

    /**
     * 快速创建实例
     * @param array $attribute
     * @return mixed
     */
    public static function create(array $attribute)
    {
        $model = new static ($attribute);
        if ($model->save()) {
            return $model;
        }
        return false;
    }

    /**
     * 设置支付状态
     * @param string $paymentId
     * @param int $status
     * @param array $params
     * @return bool
     */
    public static function setPayStatus($paymentId, $status, $params)
    {
        if (($payment = static::findOne(['id' => $paymentId])) == null) {
            return false;
        }
        if (static::STATE_SUCCESS == $payment->trade_state) {
            return true;
        }
        if ($status == true) {
            $payment->updateAttributes([
                'pay_id' => $params['pay_id'],
                'trade_state' => static::STATE_SUCCESS,
                'note' => $params['message']
            ]);//标记支付已经完成
            /** @var \yuncms\payment\OrderInterface $orderModel */
            $orderModel = $payment->model;
            if (!empty($payment->model_id) && !empty($orderModel)) {
                $orderModel::setPayStatus($payment->model_id, $paymentId, $status, $params);
            }
            return true;
        }
        return false;
    }
}