<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use yii\grid\GridView;
use app\models\chat\Messages;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\chat\SupportChat */
/* @var $messagesDataProvider yii\data\ActiveDataProvider */
/* @var $messagesModel app\models\chat\Messages */

$this->title = $model->subject;
$this->params['breadcrumbs'][] = ['label' => 'Support Chats', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

?>
<style>
.row {
 display: flex;
}
.col-6{
    width: 50% !important;
    padding: 10px;
}

/* Основной контейнер чата */
.chat-container {
  background: white;
  display: flex;
  flex-direction: column;
  height: 800px; /* Задайте нужную высоту контейнера */
}

/* Контейнер сообщений */
.chat-messages {
  overflow-y: scroll;
  padding: 10px;
  scroll-behavior: smooth;
  height: 645px;
}

/* Стили сообщений */
.message-left {
  margin-bottom: 10px;
}

/* Стили сообщений */
.message-right {
  margin-bottom: 10px;
  margin-left: 50%;
}

.message-content {
  background-color: #f2f2f2;
  padding: 5px;
  border-radius: 5px;
  width:50%;
}

.message-admin {
  background-color: #f2f2f2;
  padding: 5px;
  border-radius: 5px;
  border: 1px solid #3c8dbc !important;
}

.message-date {
  font-size: 8px;
  color: gray;
}

/* Genderе ввода сообщения */
.chat-input {
  border-top: 1px solid #ccc;
}

.chat-input textarea {
  width: 100%;
  resize: none;
  padding: 5px;
  border: 1px solid #ccc;
  border-radius: 5px;
  height: 60px;
}

.chat-input .form-group {
  margin-top: 10px;
  text-align: right;
}

.left {
    float: left;
}

.right {
    float: right;
}


</style>

<div class="content-wrapper">
    <section class="content-header">
        <h1><?=$this->title;?></h1>
        
        <ol class="breadcrumb">
            <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/'])?>"><i class="fa fa-dashboard"></i> Home</a></li>
            <li class="active"><?=$this->title;?></li>
        </ol>
    </section>
    <section class="content">
    <div class="row">
        <div class="col-6">
        <?php if($userModel = app\models\user\User::findOne($sender_id)): ?>
          <?= DetailView::widget([
            'model' => $userModel,
            'attributes' => [
                'id',
                'name',
                'lastname',
                'phone',
                'email',
                'gender',
                'birthday',
                'addres_legal',
                'date'
            ],
          ]) ?>
        <?php else:?>
        <p>No data</p>
        <?php endif;?>
        </div>
        <?php if(is_array($messagesDataProvider->getModels()) && count($messagesDataProvider->getModels()) > 0 ): ?>
        <div class="chat-container col-6">
            <!-- Вывод сообщений чата -->
            <div id = "chat-messages" class="chat-messages">
                <?php foreach ($messagesDataProvider->getModels() as $message): ?>
                    <div class="message-<?= $message->user->role == 1? 'right' : 'left' ?>">
                        <div class="<?= $message->user->role == 1? 'message-admin' : 'message-content' ?>"><?= $message->message ?></div>
                        <div class="message-date"><?= $message->date ?> - <?= $message->user->name?></div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Форма для отправки сообщения -->
            <div class="chat-input">
                <?php $form = ActiveForm::begin(['action' => ['view', 'id' => $id]]) ?>
                    <?= $form->field($messagesModel, 'message')->textarea(['rows' => 4])->label(false) ?>
                    <div class="form-group">
                        <?= Html::submitButton('Отправить', ['class' => 'btn btn-primary']) ?>
                    </div>
                <?php ActiveForm::end() ?>
            </div>
        </div>
        <?php else:?>
        <p>No message</p>
        <?php endif;?>
    </div>
    </section>

</div>

<script>
    // Прокрутка контейнера сообщений вниз при загрузке страницы
    document.addEventListener("DOMContentLoaded", function() {
        var chatMessages = document.getElementById('chat-messages');
        chatMessages.scrollTop = chatMessages.scrollHeight;
    });
</script>