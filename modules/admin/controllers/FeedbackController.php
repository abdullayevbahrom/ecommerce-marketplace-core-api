<?php
namespace app\modules\admin\controllers;

use Yii;
use yii\web\Controller;
use yii\web\HttpException;

use app\models\user\User;
use app\models\chat\Messages;
use app\models\chat\MessageRoom;
use yii\data\ActiveDataProvider;
use yii\web\NotFoundHttpException;

class FeedbackController extends Controller{
	public $user;
    
    public function beforeAction($action) {
        $this->enableCsrfValidation = false;
        if (Yii::$app->user->isGuest) {
            return $this->redirect(['/admin/default']);
        }
        $this->user = User::find()->with('moderatorAccess', 'moderatorAccess.moderator')->where(['id'=>Yii::$app->user->identity->id])->one();

        if (!$this->user) {
            Yii::$app->user->logout(false);
            return $this->redirect(["/admin/default"]);
        }

        if (($this->user->role == User::ROLE_MODERATOR)) {
            $accesses = array();

            if ($this->user && $this->user->moderatorAccess) {
                foreach ($this->user->moderatorAccess as $v) {
                    if ($v && $v->moderator) {
                        $accesses[] = $v->moderator->url;
                    }
                }
            }

            if (!in_array('feedback', $accesses)) {
                throw new HttpException(403, 'Error access');
            }
        }

        return parent::beforeAction($action);
    }

    public function actionIndex()
    {
        $dataProvider = new ActiveDataProvider([
            'query' => MessageRoom::find()->orderBy('id DESC'),
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single SupportChat model.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        $model = MessageRoom::findOne($id);

        $messagesDataProvider = new ActiveDataProvider([
            'query' => Messages::find()->where(['message_room_id' => $model->id])->orderBy(['date' => SORT_ASC]),
        ]);

        
        $messagesModel = new Messages();

        if ($messagesModel->load(Yii::$app->request->post())) {
            $messagesModel->message_room_id = $model->id;
            $messagesModel->user_id = Yii::$app->user->id;
            $messagesModel->status = Messages::STATUS_UNREAD;
            $messagesModel->save();
            Yii::$app->session->setFlash('success', 'Message sent successfully.');
            return $this->refresh();
        }

        return $this->render('view', [
            'id' => $id,
            'sender_id' => $model->sender_id,
            'messagesDataProvider' => $messagesDataProvider,
            'messagesModel' => $messagesModel,
        ]);
    }

    /**
     * Deletes an existing SupportChat model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        Yii::$app->session->setFlash('success', 'Chat deleted successfully.');
        return $this->redirect(['index']);
    }

    /**
     * Finds the SupportChat model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return SupportChat the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = MessageRoom::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }

}
