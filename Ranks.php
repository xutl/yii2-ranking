<?php
/**
 * @link http://www.tintsoft.com/
 * @copyright Copyright (c) 2012 TintSoft Technology Co. Ltd.
 * @license http://www.tintsoft.com/license/
 */

namespace xutl\ranks;

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
     */
    public $redis;

    /**
     * @var Client
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
     * @param $scores
     * @return mixed
     */
    public function addScores($identity, $scores)
    {
        $key = $this->prefix . date('Ymd');
        return $this->client->zIncrBy($key, $scores, $identity);
    }

    /**
     * 获得一天排名
     * @param $date
     * @param $start
     * @param $stop
     * @return mixed
     */
    protected function getOneDayRankings($date, $start, $stop)
    {
        $key = $this->prefix . $date;
        return $this->client->zRevRange($key, $start, $stop, true);
    }

    /**
     * 获得多天排名
     * @param $dates
     * @param $outKey
     * @param $start
     * @param $stop
     * @return mixed
     */
    protected function getMultiDaysRankings($dates, $outKey, $start, $stop)
    {
        $keys = array_map(function ($date) {
            return $this->prefix. $date;
        }, $dates);

        $weights = array_fill(0, count($keys), 1);
        $this->client->zUnion($outKey, $keys, $weights);
        return $this->client->zRevRange($outKey, $start, $stop, true);
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
     * 获取当前月份日期
     * @return array
     */
    public static function getCurrentMonthDates()
    {
        $dt = Carbon::now();
        $days = $dt->daysInMonth;

        $dates = array();
        for ($day = 1; $day <= $days; $day++) {
            $dt->day = $day;
            $dates[] = $dt->format('Ymd');
        }
        return $dates;
    }

    /**
     * 获取当前月份Top 10
     * @return mixed
     */
    public function getCurrentMonthTop10()
    {
        $dates = self::getCurrentMonthDates();
        return $this->getMultiDaysRankings($dates, $this->prefix.'current_month', 0, 9);
    }
}