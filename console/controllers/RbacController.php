<?php
namespace console\controllers;
use Yii;
use yii\console\Controller;
use common\models\User;
use yii\db\Exception;

class RbacController extends Controller
{
    public function actionInit()
    {
        $auth = Yii::$app->authManager;
        try {
            $auth->removeAll();


            $createPost = $auth->createPermission('admin');
            $createPost->description = '';
            $auth->add($createPost);


            $author = $auth->createRole('author');
            $auth->add($author);
            $auth->addChild($author, $createPost);


            $auth->assign($author, 2);
        }catch (Exception $e){
            throw new $e;
    }


    }
    public function actionAssgin(){
        $auth = Yii::$app->authManager;
        try {
            $user = new User();
            $user->username = $this->username;
            $user->email = $this->email;
            $user->setPassword($this->password);
            $user->generateAuthKey();
            $user->save(false);

            $authorRole = $auth->getRole('author');
            $auth->assign($authorRole, $user->getId());

        }catch (Exception $e){
            throw new $e;
        }
    }
}
