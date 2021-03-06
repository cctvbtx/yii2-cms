<?php
namespace core\modules\admin\controllers;

use core\modules\admin\forms\SettingForm;
use core\modules\admin\models\Admin;
use core\modules\home\models\Contact;
use core\modules\news\models\News;
use core\modules\products\models\Products;
use humhub\modules\custom_pages\models\forms\SettingsForm;
use Yii;
use core\modules\admin\components\Controller;
use core\modules\admin\forms\LoginForm;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\captcha\CaptchaAction;

class IndexController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        $behaviors = [
            'access' => [
                'class' => AccessControl::class,
                'user' => 'admin',
                'rules' => [
                    [
                        'actions' => ['login', 'error'],
                        'allow' => true,
                    ],
                    [
                        'actions' => [],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],

            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];

        return ArrayHelper::merge($behaviors, parent::behaviors());
    }

    public function actions()
    {
        $captcha = [
            'class' => CaptchaAction::class,
            'backColor' => 0x66b3ff,
            'maxLength' => 4,
            'minLength' => 4,
            'padding' => 6,
            'height' => 34,
            'width' => 100,
            'foreColor' => 0xffffff,
            'offset' => 13,
        ];
        if (YII_ENV_TEST) $captcha = array_merge($captcha, ['fixedVerifyCode' => 'testme']);
        return [
            'captcha' => $captcha,
        ];
    }

    /**
     * Home Index
     */
    public function actionIndex()
    {
        $news_count = News::find()->where(['status' => News::STATUS_ENABLED])->count();
        $product_count = Products::find()->where(['status' => Products::STATUS_ENABLED])->count();
        $contact_count = Contact::find()->where(['<>', 'status', Contact::STATUS_DELETED])->count();
        $manager_user_count = Admin::find()->where(['status' => Admin::STATUS_ACTIVE])->count();

        return $this->render(
            'index',
            compact('news_count', 'product_count', 'contact_count', 'manager_user_count')
        );
    }

    /**
     * User login
     */
    public function actionLogin()
    {
        $this->layout = 'main-login';
        if (!Yii::$app->admin->isGuest) {
            return $this->redirect(['/admin']);
        }
        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            $returnUrl = Yii::$app->admin->returnUrl;
            if ($returnUrl == '/index.php') {
                $returnUrl = Url::to(['/admin']);
            }
            return $this->redirect($returnUrl);
        } else {
            return $this->render('login', [
                'model' => $model
            ]);
        }
    }

    /**
     * User logout
     */
    public function actionLogout()
    {
        Yii::$app->admin->logout();
        return $this->redirect(['login']);
    }

    /**
     * Ajax setting
     */
    public function actionAjaxLayoutSetting()
    {
        $params = Yii::$app->request->post();
        if (isset($params['name']) && isset($params['value'])) {
            $this->module->settings->set($params['name'], $params['value']);
        }
    }

    /**
     * Set language
     */
    public function actionSetting()
    {
        $form = new SettingForm;
        if ($form->load(Yii::$app->request->post()) && $form->validate() && $form->save()) {
            Yii::$app->getSession()->setFlash(
                'success',
                Yii::t('FileModule.controllers_FileController', 'Setting successfully.')
            );
            return $this->redirect(['setting']);
        }

        return $this->render('setting', [
            'model' => $form
        ]);
    }
}
