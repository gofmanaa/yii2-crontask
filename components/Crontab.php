<?php
/**
 * User: gofmana
 * Date: 10/13/15
 * Time: 10:56 AM
 */

namespace gofmanaa\crontask\components;


use yii\helpers\ArrayHelper;
use yii\helpers\VarDumper;

class Crontab extends \yii\base\Component{

    public $directory	= NULL;
    public $filename	= ".crons";
    public $crontabPath	= NULL;
    public $cronGroup   = NULL;
    protected $jobs		= [];
    protected $handle	= NULL;


    /**
     * Initializes the Component.
     * This method is invoked at the end of the constructor after the object is initialized with the
     * given configuration.
     */
    public function init()
    {
        parent::init();
        $home = $this->drush_server_home();
        if(null == $home){
            exit('Con\'t get user home directory');
        }
        $result	=(!$this->directory) ? $this->setDirectory($home.DIRECTORY_SEPARATOR) : $this->setDirectory($this->directory);
        if(!$result)
            exit('Directory error');
        $result	=(!$this->filename) ? $this->createCronFile(".crons") : $this->createCronFile($this->filename);
        if(!$result)
            exit('File error');

        $this->loadJobs();

    }

    /**
     * Return the user's home directory.
     */
    private  function drush_server_home() {
        // Cannot use $_SERVER superglobal since that's empty during UnitUnishTestCase
        // getenv('HOME') isn't set on Windows and generates a Notice.
        $home = getenv('HOME');
        if (!empty($home)) {
            // home should never end with a trailing slash.
            $home = rtrim($home, '/');
        }
        elseif (!empty($_SERVER['HOMEDRIVE']) && !empty($_SERVER['HOMEPATH'])) {
            // home on windows
            $home = $_SERVER['HOMEDRIVE'] . $_SERVER['HOMEPATH'];
            // If HOMEPATH is a root directory the path can end with a slash. Make sure
            // that doesn't happen.
            $home = rtrim($home, '\\/');
        }
        return empty($home) ? NULL : $home;
    }



    /**
     *	Add a job
     *
     *	If any parameters are left NULL then they default to *
     *
     *	A hyphen (-) between integers specifies a range of integers. For
     *	example, 1-4 means the integers 1, 2, 3, and 4.
     *
     *	A list of values separated by commas (,) specifies a list. For
     *	example, 3, 4, 6, 8 indicates those four specific integers.
     *
     *	The forward slash (/) can be used to specify step values. The value
     *	of an integer can be skipped within a range by following the range
     *	with /<integer>. For example, 0-59/2 can be used to define every other
     *	minute in the minute field. Step values can also be used with an asterisk.
     *	For instance, the value * /3 (no space) can be used in the month field to run the
     *	task every third month...
     *
     *	@param	string	$command	Command
     *	@param	mixed	$min		Minute(s)... 0 to 59
     *	@param	mixed	$hour		Hour(s)... 0 to 23
     *	@param	mixed	$day		Day(s)... 1 to 31
     *	@param	mixed	$month		Month(s)... 1 to 12 or short name
     *	@param	mixed	$dayofweek	Day(s) of week... 0 to 7 or short name. 0 and 7 = sunday
     *  @return Crontab return this
     */
    function addJob($command, $min=NULL, $hour=NULL, $day=NULL, $month=NULL, $dayofweek=NULL, $groupName=NULL)
    {
        $this->jobs[] = new Cronjob($command, $min, $hour, $day, $month, $dayofweek, $groupName);
        return $this;

    }


    /**
     *	Add an application job
     */
    function addApplicationJob($entryScript, $commandName, $parameters = array(), $min=NULL, $hour=NULL, $day=NULL, $month=NULL, $dayofweek=NULL, $groupName=NULL)
    {
        $this->jobs[] = new CronApplicationJob($entryScript, $commandName, $parameters, $min, $hour, $day, $month, $dayofweek, $groupName);

        return $this;
    }

    /**
     * Add job object
     * @param mixed $job CronApplicationJob or Cronjob
     * @return Crontab
     */
    public function add($job)
    {
        if($job instanceof CronApplicationJob OR $job instanceof Cronjob)
            $this->jobs[] = $job;
        return $this;
    }



    /**
     *	Write cron command to file. Make sure you used createCronFile
     *	before using this function of it will return false
     *  @return Crontab return this or false
     */
    function saveCronFile(){
        $this->emptyCrontabFile();

        foreach ($this->jobs as $job)
        {
            /**
             * @var $job Cronjob
             */

            if(!fwrite($this->handle, $job->getJobCommand()))
                return false;
        }

        return $this;
    }


    /**
     *	Save cron in system
     *	@return boolean this if successful else false
     */
    function saveToCrontab(){

        if(!$this->filename)
            exit('No name specified for cron file');

        if(exec($this->crontabPath." crontab ".$this->directory.$this->filename))
            return $this;
        else
            return false;
    }


    /**
     * Get jobs
     * @return array jobs
     */
    public function getJobs()
    {
        return $this->jobs;
    }

    /**
     * Remove a job with given offset
     * @return Crontab
     */
    public function removeJob($offset = NULL)
    {
        if($offset !== NULL)
            unset($this->jobs[$offset]);

        return $this;
    }

    /**
     * remove all jobs
     * @return Crontab
     */
    public function eraseJobs()
    {
        foreach($this->jobs as $i => $job){
            /** @var $job Cronjob */
            if($job->getGroup() == $this->cronGroup){
                $this->removeJob($i);
            }
        }

        return $this;
    }




    /*********************************/
    /********* Protected *************/
    /*********************************/

    /**
     *	Set the directory path. Will check it if it exists then
     *	try to open it. Also if it doesn't exist then it will try to
     *	create it, makes it with mode 0700
     *
     *	@param	string	$directory	Directory, relative or full path
     *	@access	public
     *  @return Crontab return this
     */
    protected function setDirectory($directory){
        if(!$directory) return false;

        if(is_dir($directory)){
            if($dh=opendir($directory)){
                $this->directory=$directory;
                return $this;
            }else
                return false;
        }else{
            if(mkdir($directory, 0700)){
                $this->directory=$directory;
                return $this;
            }
        }
        return false;
    }


    /**
     *	Create cron file
     *
     *	This will create a cron job file for you and set the filename
     *	of this class to use it. Make sure you have already set the directory
     *	path variable with the consructor. If the file exists and we can write
     *	it then return true esle false. Also sets $handle with the resource handle
     *	to the file
     *
     *	@param	string	$filename	Name of file you want to create
     *	@access	public
     *  @return Crontab return this or false
     */
    protected function createCronFile($filename=NULL){
        if(!$filename)
            return false;

        if(file_exists($this->directory.$filename)){
            if($this->openFile($handle,$filename, 'a+')){
                $this->handle=&$handle;
                $this->filename=$filename;
                return $this;
            }else
                return false;
        }

        if(!$this->openFile($handle,$filename, 'a+'))
            return false;
        else{
            $this->handle=&$handle;
            $this->filename=$filename;
            return $this;
        }
    }



    /**
     * Load active jobs from system crontab and merge with file jobs
     */
    protected function loadJobs(){
        $command = $this->crontabPath."crontab -l 2>&1";
        $outputLines = [];
        $fileLine    = [];
        $systemJobs  = [];
        exec($command, $outputLines);

        foreach ($outputLines as $outputLine) {

            if (stripos($outputLine, 'no crontab') !== 0) {
                $outputLine = trim(trim($outputLine), "\t");
                $systemJobs[] = $outputLine;
            }
        }

        fseek($this->handle, 0);
        while (! feof ($this->handle)) {
            $line = fgets($this->handle);
            $line = trim(trim($line), "\t");

            if (!empty($line)) {
                $fileLine[] = $line;
            }
        }
        $mergedJobs = array_merge($systemJobs,$fileLine);
        $mergedJobs = array_unique($mergedJobs);

        foreach($mergedJobs as $job){

            if(CronApplicationJob::isApplicationJob($job))
            {
                $obj = CronApplicationJob::parseFromCommand($job);

                if($obj !== FALSE){
                    $this->jobs[] = $obj;
                }
            }
            else
            {

                $obj = Cronjob::parseFromCommand($job);
                if($obj !== FALSE) {
                    $this->jobs[] = $obj;
                }

            }
        }

    }


    /**
     * Empty crontab file
     * @return Crontab
     */
    protected function emptyCrontabFile()
    {
        $this->closeFile();
        $this->openFile($this->handle,$this->filename, 'w');
        $this->closeFile();
        $this->openFile($this->handle,$this->filename, 'a');

        return $this;
    }


    /**
     * Close crontab file
     */
    protected function closeFile()
    {
        fclose($this->handle);
    }

    /**
     * Open crontab file
     * @param ressource $handle
     * @param string $filename
     * @param string $accessType
     */
    protected function openFile(&$handle, $filename, $accessType = 'a+')
    {
        return $handle = fopen($this->directory.$filename, $accessType);

    }

}


class Cronjob
{
    const GROUP_PREFIX = 'GROUP=';
    protected $minute		= NULL;
    protected $hour			= NULL;
    protected $day			= NULL;
    protected $month		= NULL;
    protected $dayofweek	= NULL;
    protected $command		= NULL;
    protected $groupName    = NULL;


    function __construct($command, $min=NULL, $hour=NULL, $day=NULL, $month=NULL, $dayofweek=NULL,$groupName=NULL)
    {
        $this->setMinute($min);
        $this->setHour($hour);
        $this->setDay($day);
        $this->setMonth($month);
        $this->setDayofweek($dayofweek);
        $this->command = $command;
        $this->setGroup($groupName);

        return $this;
    }

    /**
     * Return the system command for the object
     */
    public function getJobCommand()
    {
        return $this->minute." ".$this->hour." ".$this->day." ".$this->month." ".$this->dayofweek." ".$this->getCommand()." ".$this->getGroupName(). "\n";
    }

    /**
     * Return the command
     */
    public function getCommand()
    {
        return $this->command;
    }

    /**
     * parse system job command and return an object
     * Works only for regular entry
     */
    static function parseFromCommand($command)
    {
        $groupName = NULL;
        $vars = preg_split("/[ \t]/",ltrim($command, " \t"), 6);

        if(count($vars) < 5)
            return false;

        $min 	     = $vars[0];
        $hour 		 = $vars[1];
        $day		 = $vars[2];
        $month		 = $vars[3];
        $dayofweek 	 = $vars[4];
        $command 	 = $vars[5];

        if($groupStr   = strstr($command, '#'.self::GROUP_PREFIX) ){
            $groupStr  = preg_split("/[ \t]/",$groupStr, 1);
            $groupName = trim($groupStr[0]," \t".'#'.self::GROUP_PREFIX);

            $command   = str_replace($groupStr[0],'',$vars[5]);
            $command   = rtrim($command, " \t");
        }


        return new Cronjob($command, $min, $hour, $day, $month, $dayofweek, $groupName);
    }

    /* setter */

    public function setMinute($min)
    {
        if($min=="0")
            $this->minute=0;
        elseif($min)
            $this->minute=$min;
        else
            $this->minute="*";
    }

    public function setHour($hour)
    {
        if($hour=="0")
            $this->hour=0;
        elseif($hour)
            $this->hour=$hour;
        else
            $this->hour="*";
    }

    public function setDay($day)
    {
        $this->day=($day) ? $day : "*";
    }

    public function setMonth($month)
    {
        $this->month=($month) ? $month : "*";
    }

    public function setdayofweek($dayofweek)
    {
        $this->dayofweek=($dayofweek) ? $dayofweek : "*";
    }

    public function setGroup($groupName)
    {
        $this->groupName = $groupName;
    }

    /* getter */

    public function getMinute()
    {
        return $this->minute;
    }

    public function getHour()
    {
        return $this->hour;
    }

    public function getDay()
    {
        return $this->day;
    }

    public function getMonth()
    {
        return $this->month;
    }

    public function getDayofweek()
    {
        return $this->dayofweek;
    }

    public function getGroup()
    {
       return $this->groupName;
    }

    public function getGroupName()
    {
        return $groupName =  (($this->groupName) ? '#' . self::GROUP_PREFIX .$this->groupName : NULL);
    }
}


class CronApplicationJob extends Cronjob
{
    protected $entryScript = NULL;
    protected $commandName = NULL;
    protected $parameters  = array();


    function __construct($entryScript, $commandName, $parameters = array(), $min=NULL, $hour=NULL, $day=NULL, $month=NULL, $dayofweek=NULL,$groupName=NULL)
    {
        $this->entryScript = $entryScript;
        $this->commandName = $commandName;
        $this->parameters = $parameters;
        $command = $this->getCommand();
        parent::__construct($command, $min, $hour, $day, $month, $dayofweek,$groupName);

        return $this;

    }


    /**
     * Return the Application command
     */
    public function getCommand()
    {
        $command = '/usr/bin/php '.$this->entryScript . ' ' . $this->commandName;

        if(!empty($this->parameters)) {
            foreach ($this->parameters as $parameter)
                $command .= ' ' . $parameter;
        }

        return $command;
    }

    /**
     * Check if the given command would be an ApplicationJob
     */
    static function isApplicationJob($line)
    {
        $vars = preg_split("/[ \t]/",ltrim($line), 6);

        if(count($vars) < 5) {
            return false;
        }
        return true;
    }


    /* setter */

    public function setParams($params)
    {
        $this->parameters = $params;
    }

    public function setEntryScript($entryScript)
    {
        $this->entryScript = $entryScript;
    }

    public function setCommandName($commandName)
    {
        $this->commandName = $commandName;
    }



    /* getter */

    public function getParams()
    {
        return $this->parameters;
    }

    public function getEntryScript()
    {
        return $this->entryScript;
    }

    public function getCommandName()
    {
        return $this->commandName;
    }

}