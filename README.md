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


Usage
-----
Add to console config:
   
```php
return [
    'modules' => [
        'crontask' => [
            'class' => 'gofmanaa\crontask\Module',
            'fileName'=>'cron.txt',
        ],
    ],
]
```


```php
  'params' =>[
        'tasks'=>[
            'dosomething'=>
                   [
                       'command'=>'path/to/controller/action',
                       'min'=>'*/1',
                       'hour'=>'*',
                       'day'=>'*',
                       'month'=>'*',
                       'dayofweek'=>'*'
    
                   ],
            'dosomething2'=>
                [
                    'command'=>'path/to/script',
                    'min'=>'*/2',
    
                ],
        ]
  ]
```

Console command
-----
```
- crontask/cron                    Provides cron information about console commands.
    crontask/cron/index (default)  Displays available commands or the detailed information
    crontask/cron/ls               List All Cron Jobs
    crontask/cron/start            Start cron tasks
    crontask/cron/stop             Stop cron
```