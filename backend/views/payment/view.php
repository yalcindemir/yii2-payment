<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use yuncms\admin\widgets\Jarvis;
use yuncms\payment\models\Payment;

/* @var $this yii\web\View */
/* @var $model yuncms\payment\models\Payment */

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => Yii::t('payment', 'Manage Payment'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<section id="widget-grid">
    <div class="row">
        <article class="col-xs-12 col-sm-12 col-md-12 col-lg-12 payment-view">
            <?php Jarvis::begin([
                'noPadding' => true,
                'editbutton' => false,
                'deletebutton' => false,
                'header' => Html::encode($this->title),
                'bodyToolbarActions' => [
                    [
                        'label' => Yii::t('payment', 'Manage Payment'),
                        'url' => ['index'],
                    ],
                    [
                        'label' => Yii::t('payment', 'Update Payment'),
                        'url' => ['update', 'id' => $model->id],
                        'options' => ['class' => 'btn btn-primary btn-sm']
                    ],
                    [
                        'label' => Yii::t('payment', 'Delete Payment'),
                        'url' => ['delete', 'id' => $model->id],
                        'options' => [
                            'class' => 'btn btn-danger btn-sm',
                            'data' => [
                                'confirm' => Yii::t('app', 'Are you sure you want to delete this item?'),
                                'method' => 'post',
                            ],
                        ]
                    ],
                ]
            ]); ?>
            <?= DetailView::widget([
                'model' => $model,
                'attributes' => [
                    'id',
                    'order_id',
                    'pay_id',
                    'user_id',
                    'name',
                    'gateway',
                    'currency',
                    'money',
                    [
                        'label' => Yii::t('payment', 'Pay Type'),
                        'value' => function ($model) {
                            if ($model->pay_type == Payment::TYPE_ONLINE) {
                                return Yii::t('payment', 'Online Payment');
                            } else if ($model->pay_type == Payment::TYPE_OFFLINE) {
                                return Yii::t('payment', 'Office Payment');
                            } else if ($model->pay_type == Payment::TYPE_RECHARGE) {
                                return Yii::t('payment', 'Recharge Payment');
                            } else if ($model->pay_type == Payment::TYPE_COIN) {
                                return Yii::t('payment', 'Coin Payment');
                            }
                        },
                        'format' => 'raw'
                    ],
                    [
                        'label' => Yii::t('payment', 'Pay State'),
                        'value' => function ($model) {
                            if ($model->pay_state == Payment::STATUS_NOT_PAY) {
                                return Yii::t('payment', 'State Not Pay');
                            } else if ($model->pay_state == Payment::STATUS_SUCCESS) {
                                return Yii::t('payment', 'State Success');
                            } else if ($model->pay_state == Payment::STATUS_FAILED) {
                                return Yii::t('payment', 'State Failed');
                            } else if ($model->pay_state == Payment::STATUS_REFUND) {
                                return Yii::t('payment', 'State Refund');
                            } else if ($model->pay_state == Payment::STATUS_CLOSED) {
                                return Yii::t('payment', 'State Close');
                            } else if ($model->pay_state == Payment::STATUS_REVOKED) {
                                return Yii::t('payment', 'State Revoked');
                            } else if ($model->pay_state == Payment::STATUS_ERROR) {
                                return Yii::t('payment', 'State Error');
                            }
                        },
                       'format' => 'raw'
                    ],
                    'ip',
                    'note:ntext',
                    'created_at:datetime',
                    'updated_at:datetime',
                ],
            ]) ?>
            <?php Jarvis::end(); ?>
        </article>
    </div>
</section>