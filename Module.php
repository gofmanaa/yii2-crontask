<?php
/**
 * User: gofmana
 * Date: 11/24/15
 * Time: 5:17 PM
 */

namespace gofmanaa\crontask;


use Yii;
use yii\base\BootstrapInterface;
use yii\helpers\Inflector;


class Module extends \yii\base\Module implements BootstrapInterface
{

    public $defaultRoute = 'cron';
    public $nameComponent = 'crontab';
    public $fileName = '.crons';
    public $fileDir = null; //default /home/<username>
    public $crontabPath = null;
    public $tasks = [];
    public $params = [];
    public $cronGroup = null; //mast be unique for any app on server

    /**
     * Initializes the module.
     */
    public function init()
    {
        if(is_null($this->cronGroup)){
            $this->cronGroup = Inflector::slug( Yii::$app->id .'-'. Yii::getAlias('@app'));
        } else {
            $this->cronGroup = Inflector::slug( $this->cronGroup );
        }
        $this->setComponents([
            $this->nameComponent => [
                'class'      => 'gofmanaa\crontask\components\Crontab',
                'filename'   => $this->fileName,
                'directory'  => $this->fileDir,
                'crontabPath'=> $this->crontabPath,
                'cronGroup'  => $this->cronGroup
            ],
        ]);

        parent::init();


    }

    public function getUniqueId(){
        return $this->id;
    }

    public function bootstrap($app){
        if ($app instanceof \yii\console\Application) {
            $app->controllerMap[$this->id] =
                [
                    'class'  => 'gofmanaa\crontask\console\CronController',
                    'module' => $this,
                ];
        }

    }


}