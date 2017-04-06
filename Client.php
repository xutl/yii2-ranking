<?php
/**
 * @link http://www.tintsoft.com/
 * @copyright Copyright (c) 2012 TintSoft Technology Co. Ltd.
 * @license http://www.tintsoft.com/license/
 */

namespace xutl\ranking;


use yii\base\Component;
use yii\base\InvalidConfigException;
use Predis\Client as RedisClient;

/**
 * Class Client
 * @package xutl\ranking
 */
class Client extends Component
{
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
        $this->redis = new RedisClient($this->redis);
    }

    /**
     * 获取指定榜单
     * @param string $ranking
     * @return Ranking
     */
    public function getRankingRef($ranking)
    {
        return new Ranking([
            'redis' => $this->redis,
            'prefix' => $ranking,
        ]);
    }
}