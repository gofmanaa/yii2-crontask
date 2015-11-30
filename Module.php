<?php
/**
 * User: gofmana
 * Date: 11/24/15
 * Time: 5:17 PM
 */

namespace crontask;


use Yii;
use yii\base\BootstrapInterface;


class Module extends \yii\base\Module implements BootstrapInterface
{

    public $defaultRoute = 'cron';
    public $nameComponent = 'crontab';
    public $fileName = 'cron.txt';
    public $fileDir = null;
    public $crontabPath = null;
    public $tasks = [];

    /**
     * Initializes the module.
     */
    public function init()
    {
        $this->setComponents([
            $this->nameComponent => [
                'class' => 'crontask\components\Crontab',
                'filename' => $this->fileName,
                'directory'=> $this->fileDir,
                'crontabPath'=>$this->crontabPath,
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
                    'class' => 'crontask\console\CronController',
                    'module' => $this,
                ];
        }

    }


}