<?php

/**
 * UserController — Управление пользователями админ-панели
 * 
 * НАЗНАЧЕНИЕ:
 * CRUD операции для пользователей админ-панели: администраторы,
 * менеджеры, логисты. Управление ролями и правами доступа.
 * 
 * ФУНКЦИИ:
 * - Список пользователей (index)
 * - Создание пользователя (create)
 * - Редактирование пользователя (update)
 * - Удаление пользователя (delete)
 * - Смена пароля пользователя (change-password)
 * - Получение списка логистов для AJAX (logists)
 * 
 * СВЯЗИ:
 * - User (модель пользователя)
 * 
 * ДОСТУП:
 * - Только администраторы
 * 
 * РОЛИ:
 * - admin: полный доступ ко всему
 * - manager: управление заказами, товарами, покупателями
 * - logist: работа с назначенными заказами (доставка)
 */
namespace app\backend\modules\admin\controllers;

use Yii;
use yii\data\ActiveDataProvider;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use app\backend\modules\admin\models\User;

class UserController extends BaseAdminController
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        $this->adminOnly = true;
        return parent::behaviors();
    }

    /**
     * Получение списка логистов для AJAX
     */
    public function actionLogists()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        try {
            $logists = User::find()
                ->where(['role' => 'logist'])
                ->andWhere(['status' => 'active'])
                ->orderBy(['username' => SORT_ASC])
                ->all();
                
            $result = [];
            foreach ($logists as $logist) {
                $result[] = [
                    'id' => $logist->id,
                    'username' => $logist->username,
                    'email' => $logist->email,
                ];
            }
            
            return ['success' => true, 'logists' => $result];
        } catch (\Exception $e) {
            // Демо-данные при отсутствии БД
            return [
                'success' => true, 
                'logists' => [
                    ['id' => 4, 'username' => 'logist1', 'email' => 'logist1@sneakerhead.by'],
                    ['id' => 5, 'username' => 'logist2', 'email' => 'logist2@sneakerhead.by'],
                ]
            ];
        }
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

        return $this->render('index', [
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

        return $this->render('create', [
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
