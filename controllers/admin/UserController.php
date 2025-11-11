<?php

namespace app\controllers\admin;

use Yii;
use yii\data\ActiveDataProvider;
use yii\web\NotFoundHttpException;
use app\models\User;

/**
 * UserController - Управление пользователями
 * 
 * Только для администраторов.
 * 
 * Методы:
 * - index() - Список пользователей
 * - create() - Создание пользователя
 * - delete($id) - Удаление пользователя
 */
class UserController extends BaseAdminController
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        
        // Все действия доступны только админам
        $behaviors['access']['rules'] = [
            [
                'allow' => true,
                'roles' => ['@'],
                'matchCallback' => function ($rule, $action) {
                    return $this->isAdmin();
                }
            ],
        ];
        
        return $behaviors;
    }

    /**
     * Список пользователей
     */
    public function actionIndex()
    {
        $dataProvider = new ActiveDataProvider([
            'query' => User::find()->orderBy(['id' => SORT_DESC]),
            'pagination' => [
                'pageSize' => 20,
            ],
        ]);

        return $this->render('/admin/users', [
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Создание нового пользователя
     */
    public function actionCreate()
    {
        $model = new User();
        $model->scenario = 'create';

        if ($model->load(Yii::$app->request->post())) {
            // Устанавливаем статус активен
            $model->status = User::STATUS_ACTIVE;
            
            // Хешируем пароль
            $model->setPassword($model->password);
            $model->generateAuthKey();
            
            if ($model->save()) {
                $currentUser = $this->getCurrentUser();
                Yii::info('Создан новый пользователь: ' . $model->username . ' (роль: ' . $model->role . ') админом #' . $currentUser->id, 'user');
                $this->flashSuccess('Пользователь успешно создан!');
                return $this->redirect(['/admin/user/index']);
            } else {
                $this->flashError('Ошибка при создании пользователя: ' . json_encode($model->errors));
            }
        }

        return $this->render('/admin/create-user', [
            'model' => $model,
        ]);
    }

    /**
     * Удаление пользователя
     * 
     * @param int $id
     */
    public function actionDelete($id)
    {
        $currentUser = $this->getCurrentUser();

        // Нельзя удалить самого себя
        if ($id == $currentUser->id) {
            $this->flashError('Нельзя удалить самого себя.');
            return $this->redirect(['/admin/user/index']);
        }

        $userToDelete = User::findOne($id);
        if ($userToDelete === null) {
            throw new NotFoundHttpException('Пользователь не найден.');
        }

        $username = $userToDelete->username;
        if ($userToDelete->delete()) {
            Yii::info('Удален пользователь: ' . $username . ' (ID: ' . $id . ') админом #' . $currentUser->id, 'user');
            $this->flashSuccess('Пользователь успешно удален.');
        } else {
            $this->flashError('Ошибка при удалении пользователя.');
        }

        return $this->redirect(['/admin/user/index']);
    }
}
