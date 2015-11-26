<?php
/**
 * User: gofmana
 * Date: 10/13/15
 * Time: 10:56 AM
 */

namespace crontask\controllers;

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
     * Displays available commands or the detailed information
     * about a particular command.
     */
    public function actionIndex()
    {
        echo $this->ansiFormat('./yii ' . $this->module->id . '/' . $this->id . '/start' . PHP_EOL, Console::FG_YELLOW);
        echo $this->ansiFormat('./yii ' . $this->module->id . '/' . $this->id . '/stop //stop all crontab -r' . PHP_EOL, Console::FG_YELLOW);
        echo $this->ansiFormat('./yii ' . $this->module->id . '/' . $this->id . '/ls //list job crontab -l' . PHP_EOL, Console::FG_YELLOW);
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

        if($tasks = \Yii::$app->params['tasks']) {
            foreach ($tasks as $task) {
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
     *  List All Cron Jobs
     */
    public function actionLs()
    {
        echo shell_exec('crontab -l') . PHP_EOL;
    }

}