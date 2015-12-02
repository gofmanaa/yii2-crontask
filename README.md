gofmanaa/yii2-crontask
======================
yii2 cron task manager

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist gofmanaa/yii2-crontask "*"
```

or add

```
"gofmanaa/yii2-crontask": "*"
```

to the require section of your `composer.json` file.


Requirements
-----

Linux OS
Yii2 

Usage
-----
Add to console config:
   
```php
return [
    'bootstrap' => [
        'crontask'
    ],
    'modules' => [
        'crontask' => [
            'class' => 'gofmanaa\crontask\Module',
            'fileName'=>'cron.txt', //optional
            'tasks'=>[
                'dosomething'=>
                            [
                                'command'=>'path/to/controller/action',
                               'min'=>'*/1',
                               'hour'=>'*',
                               'day'=>'*',
                               'month'=>'*',
                               'dayofweek'=>'*',
                            ],
                'dosomething2'=>
                            [
                                'command'=>'path/to/controller/action',
                                'min'=>'*/2',
                            ],
            ]
        ],
    ],
]
```

Console command
-----
```
- crontask                    Provides cron information about console commands.
    crontask/index (default)  Displays available commands or the detailed information
    crontask/ls               List App Cron tasks;   crontask/ls -a All cron jobs
    crontask/start            Start cron tasks,  "crontask/start dosomething2"  start single task
    crontask/stop             Stop App cron. "crontask/stop 3"  stop task by index 3 
```