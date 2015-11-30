<?php
/**
 * User: gofmana
 * Date: 10/13/15
 * Time: 10:56 AM
 */

namespace gofmanaa\crontask\console;

use crontask\Module;
use gofmanaa\crontask\components\Crontab;
use yii\console\Controller;
use yii\helpers\ArrayHelper;
use yii\helpers\Console;


/**
 * Provides cron information about console commands.
 */
class CronController extends Controller
{

    /**
     * @var Module
     */
    public $module;
    /**
     * Displays available commands or the detailed information
     * about a particular command.
     */


    public function actionIndex()
    {
        $this->run('/help', [$this->module->id]);
    }


    /**
     *  Start cron tasks
     */
    public function actionStart()
    {
        /**
         * @var $cron Crontab
         */
        $cron = $this->module->get($this->module->nameComponent);
        $cron->eraseJobs();

        if(!empty($this->module->tasks)) {
            foreach ($this->module->tasks as $task) {
                $cron->addApplicationJob(\Yii::getAlias('@app') . '/../yii', $task['command'],
                    [],
                    ArrayHelper::getValue($task, 'min'),
                    ArrayHelper::getValue($task, 'hour'),
                    ArrayHelper::getValue($task, 'day'),
                    ArrayHelper::getValue($task, 'month'),
                    ArrayHelper::getValue($task, 'dayofweek')
                );
            }
            $cron->saveCronFile(); // save to my_crontab cronfile
            $cron->saveToCrontab(); // adds all my_crontab jobs to system (replacing previous my_crontab jobs)

            echo $this->ansiFormat('Cron Tasks started.' . PHP_EOL, Console::FG_GREEN);
        } else {
            echo $this->ansiFormat('Cron do not have Tasks.' . PHP_EOL, Console::FG_GREEN);
        }
    }


    /**
     *  Stop cron
     */
    public function actionStop()
    {
        system('crontab -r');
        echo $this->ansiFormat('Cron Tasks Stopped.'. PHP_EOL, Console::FG_GREEN);
    }


    /**
     * @return string the controller ID that is prefixed with the module ID (if any).
     */
    public function getUniqueId()
    {
        return $this->id;
    }


    /**
     *  List All Cron Jobs
     */
    public function actionLs()
    {
        /**
         * @var $cron Crontab
         */
        echo shell_exec('crontab -l') . PHP_EOL;
    }

}