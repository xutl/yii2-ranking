# yii2-ranks

redis ranks

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist xutl/yii2-ranks
```

or add

```
"xutl/yii2-ranks": "~1.0.0"
```

to the require section of your `composer.json` file.

配置
----

To use this extension, you have to configure the Connection class in your application configuration:

```php
return [
    //....
    'components' => [
        'ranking' => [
           'class' => 'xutl\ranking\Ranking',
           'redis' => [
                'scheme' => 'tcp',
                'host' => '127.0.0.1',
                'port' => 6379,
                //'password' => '1984111a',
                'db' => 0
           ],
        ],
    ]
];

/** @var \xutl\ranking\Ranking $ranking */
$ranking = Yii::$app->ranking;

$ranking->addScores(1, 2);
$ranking->addScores(2, 2);
$ranking->addScores(2, 2);
$ranking->addScores(1, 2);
$ranking->addScores(3, 2);
$ranking->addScores(3, 2);
$ranking->addScores(1, 2);
$ranking->addScores(5, 2);
$ranking->addScores(6, 2);
$ranking->addScores(7, 2);
$ranking->addScores(9, 2);
$ranking->addScores(1, 2);
$ranking->addScores(1, 2);
$rankings = $ranking->getCurrentMonthTop10(date('Ymd'), 0, 9);
print_r($rankings);
exit;
```