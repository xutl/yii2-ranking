<?php
/**
 * @link http://www.tintsoft.com/
 * @copyright Copyright (c) 2012 TintSoft Technology Co. Ltd.
 * @license http://www.tintsoft.com/license/
 */

namespace xutl\ranking;

use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;
use Carbon\Carbon;
use Predis\Client;

/**
 * Class Ranks
 * @package xutl\ranks
 */
class Ranks extends Component
{
    /**
     * @var string 榜单名称
     */
    public $prefix = 'rank:';

    /**
     * @var array redis config
     * @see https://github.com/nrk/predis
     */
    public $redis;

    /**
     * @var \Predis\Client
     */
    private $client;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        if (empty ($this->redis)) {
            throw new InvalidConfigException ('The "redis" property must be set.');
        }
        $this->client = new Client($this->redis);
    }

    /**
     * 添加分数
     * @param string|int $identity
     * @param int $scores
     * @return mixed
     */
    public function addScores($identity, $scores)
    {
        $key = $this->prefix . date('Ymd');
        return $this->client->zincrby($key, $scores, $identity);
    }

    /**
     * 获取昨日TOP10
     * @return mixed
     */
    public function getYesterdayTop10()
    {
        $date = Carbon::now()->subDays(1)->format('Ymd');
        return $this->getOneDayRankings($date, 0, 9);
    }

    /**
     * 获取当前月份Top 10
     * @return mixed
     */
    public function getCurrentMonthTop10()
    {
        $dates = static::getCurrentMonthDates();
        return $this->getMultiDaysRankings($dates, 'rank:current_month', 0, 9);
    }

    /**
     * 获得指定日期的排名
     * @param string $date 20170101
     * @param int $start 开始行
     * @param int $stop 结束行
     * @return mixed
     */
    protected function getOneDayRankings($date, $start, $stop)
    {
        $key = $this->prefix . $date;
        return $this->client->zrevrange($key, $start, $stop, true);
    }

    /**
     * 获得多天排名
     * @param array $dates ['20170101','20170102']
     * @param string $outKey 输出Key
     * @param int $start 开始行
     * @param int $stop 结束行
     * @return mixed
     */
    protected function getMultiDaysRankings($dates, $outKey, $start, $stop)
    {
        $keys = array_map(function ($date) {
            return $this->prefix . $date;
        }, $dates);

        $weights = array_fill(0, count($keys), 1);
        $this->client->zunionstore($outKey, $keys, $weights);
        return $this->client->zrevrange($outKey, $start, $stop, true);
    }

    /**
     * 获取当前月份日期
     * @return array
     */
    public static function getCurrentMonthDates()
    {
        $dt = Carbon::now();
        $days = $dt->daysInMonth;

        $dates = [];
        for ($day = 1; $day <= $days; $day++) {
            $dt->day = $day;
            $dates[] = $dt->format('Ymd');
        }
        return $dates;
    }
}