<?php
/**
 * User: gofmana
 * Date: 10/13/15
 * Time: 10:56 AM
 */

namespace gofmanaa\crontask\console;

use gofmanaa\crontask\components\Cronjob;
use gofmanaa\crontask\Module;
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
     * @var $module Module
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


    protected function getYiiPath(){
        $path = \Yii::getAlias('@app') . '/../yii';
        return $this->module->yiiPath ?: $path;
    }

    /**
     * Start cron tasks
     * @param string $taskCommand
     * @throws \yii\base\InvalidConfigException
     */
    public function actionStart($taskCommand = null )
    {
        /**
         * @var $cron Crontab
         */
        $cron = $this->module->get($this->module->nameComponent);
        $cron->eraseJobs();
        $common_params = $this->module->params;
        if(!empty($this->module->tasks)) {
            if($taskCommand && isset($this->module->tasks[$taskCommand])){

                    $task = $this->module->tasks[$taskCommand];
                    $params = ArrayHelper::merge( ArrayHelper::getValue($task, 'params',[]), $common_params );

                    $cron->addApplicationJob($this->module->phpPath.' '.$this->getYiiPath(), $task['command'],
                        $params,
                        ArrayHelper::getValue($task, 'min'),
                        ArrayHelper::getValue($task, 'hour'),
                        ArrayHelper::getValue($task, 'day'),
                        ArrayHelper::getValue($task, 'month'),
                        ArrayHelper::getValue($task, 'dayofweek'),
                        $this->module->cronGroup //mast be unique for any app on server
                    );

            } else {
                    foreach ($this->module->tasks as $commandName => $task) {

                        $params = ArrayHelper::merge( ArrayHelper::getValue($task, 'params',[]), $common_params );

                        $cron->addApplicationJob($this->module->phpPath.' '.$this->getYiiPath(), $task['command'],
                            $params,
                            ArrayHelper::getValue($task, 'min'),
                            ArrayHelper::getValue($task, 'hour'),
                            ArrayHelper::getValue($task, 'day'),
                            ArrayHelper::getValue($task, 'month'),
                            ArrayHelper::getValue($task, 'dayofweek'),
                            $this->module->cronGroup //mast be unique for any app on server
                        );
                    }
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
     * @param integer $index
     * @throws \yii\base\InvalidConfigException
     */
    public function actionStop($index = null)
    {
        /**
         * @var $cron Crontab
         */
        $cron = $this->module->get($this->module->nameComponent);
        if(is_null($index)) {
            $cron->eraseJobs();
        } else {
            $cron->removeJob($index);
        }
        $cron->saveCronFile(); // save to my_crontab cronfile
        $cron->saveToCrontab(); // adds all my_crontab jobs to system (replacing previous my_crontab jobs)
        if($cron->cronGroup){
            echo $this->ansiFormat('Cron Tasks Stopped for group '.$cron->cronGroup .'.'. PHP_EOL, Console::FG_GREEN);
        }else{
            echo $this->ansiFormat('Cron Tasks Stopped.'. PHP_EOL, Console::FG_GREEN);
        }

    }


    /**
     *  Restart cron
     * @throws \yii\base\InvalidConfigException
     */
    public function actionRestart()
    {
        $this->actionStop();
        $this->actionStart();
    }

    /**
     * @return string the controller ID that is prefixed with the module ID (if any).
     */
    public function getUniqueId()
    {
        return $this->id;
    }


    /**
     *  List Application Cron Jobs; a|al All jobs
     * @param string $params
     * @throws \yii\base\InvalidConfigException
     */
    public function actionLs($params = false){
        /**
         * @var $cron Crontab
         */

        if(false == $params) {

            $cron = $this->module->get($this->module->nameComponent);
            $jobs = $cron->getJobs();

            foreach ($jobs as $index=>$job) {
                /**
                 * @var $job Cronjob
                 */
                if($job->getGroup() == $this->module->cronGroup)
                echo '['.$index.'] '. $this->ansiFormat($job->getJobCommand(), Console::FG_CYAN);
            }
        } elseif($params == 'a' || $params == 'al') {
            echo shell_exec('crontab -l') . PHP_EOL;
        }

    }

}