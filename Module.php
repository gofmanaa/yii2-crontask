<?php
/**
 * User: gofmana
 * Date: 11/24/15
 * Time: 5:17 PM
 */

namespace crontask;


use Yii;
use yii\base\Action;
use yii\console\Exception;


class Module extends \yii\base\Module
{

    public $defaultRoute = 'cron';
    public $controllerNamespace = 'crontask\controllers';

    public $nameComponent = 'crontab';

    public $fileName = 'cron.txt';
    public $fileDir = null;
    public $crontabPath = null;

    public function init()
    {
        parent::init();


        $this->setComponents([
            $this->nameComponent => [
                'class' => 'gofmanaa\crontask\components\Crontab',
                'filename' => $this->fileName,
                'directory'=> $this->fileDir,
                'crontabPath'=>$this->crontabPath,
            ],
        ]);

    }


}