<?php
/**
 * @link http://www.tintsoft.com/
 * @copyright Copyright (c) 2012 TintSoft Technology Co. Ltd.
 * @license http://www.tintsoft.com/license/
 */

namespace xutl\ranking;

use yii\base\Component;
use yii\base\InvalidConfigException;
use Carbon\Carbon;
use Predis\Client;

/**
 * Class Ranking
 * @package xutl\ranks
 */
class Ranking extends Component
{
    /**
     * @var string 榜单名称
     */
    public $prefix = 'rank:';

    /**
     * @var \Predis\Client|array
     * @see https://github.com/nrk/predis/wiki/Quick-tour
     */
    public $redis;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        if (empty ($this->redis)) {
            throw new InvalidConfigException ('The "redis" property must be set.');
        }
        $this->redis = new Client($this->redis);
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
        return $this->redis->zincrby($key, $scores, $identity);
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
     * 获取本周Top 10
     * @return mixed
     */
    public function getCurrentWeekTop10()
    {
        $dates = static::getCurrentWeekDates();
        return $this->getMultiDaysRankings($dates, 'rank:current_week', 0, 9);
    }


    /**
     * 获得指定日期的排名
     * @param string $date 20170101
     * @param int $start 开始行
     * @param int $stop 结束行
     * @return array
     */
    protected function getOneDayRankings($date, $start, $stop)
    {
        $key = $this->prefix . $date;
        return $this->redis->zrevrange($key, $start, $stop, ['withscores' => true]);
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
        $this->redis->zunionstore($outKey, $keys, $weights);
        return $this->redis->zrevrange($outKey, $start, $stop, ['withscores' => true]);
    }

    /**
     * 获取本周日期
     * @return array
     */
    public static function getCurrentWeekDates()
    {
        $dt = Carbon::now();
        $dt->startOfWeek();
        $dates = [];
        for ($day = 1; $day <= 7; $day++) {
            $dates[] = $dt->format('Ymd');
            $dt->addDay();
        }
        return $dates;
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