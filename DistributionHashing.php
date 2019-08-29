<?php
/**
 * ----------------------------------------------------------
 * date: 2019/8/28 10:47
 * ----------------------------------------------------------
 * author: Raoxiaoya
 * ----------------------------------------------------------
 * describe: 分布式哈希算法
 * ----------------------------------------------------------
 */

class DistributionHashing implements DistributionHashingInterface
{
    /**
     * 真实节点服务器
     * @var array
     */
    protected $serverList = [];

    /**
     * 虚拟节点位置
     * @var array
     */
    protected $virtualNode = [];

    /**
     * 每个真实节点拥有的虚拟节点个数
     * @var int
     */
    protected $virtualNum = 20;

    /**
     * @return int
     */
    public function getVirtualNum(): int
    {
        return $this->virtualNum;
    }

    public function __construct(array $nodes = [], int $virtualNum = null)
    {
        if (isset($virtualNum) && $virtualNum > 0) {
            $this->virtualNum = $virtualNum;
        }
        if (!empty($nodes)) {
            foreach ($nodes as $node) {
                $this->addNode($node);
            }
        }
    }

    public function addNode(string $name): DistributionHashingInterface
    {
        if (!isset($this->serverList[$name])) {
            $this->serverList[$name] = [];
            for ($i = 0; $i < $this->virtualNum; $i++) {
                $hashValue                     = $this->hashing($name . '-' . $i);
                $this->virtualNode[$hashValue] = $name;
                array_push($this->serverList[$name], $hashValue);
            }

            // 对虚拟节点从小到大排序
            ksort($this->virtualNode, SORT_NUMERIC);
        }

        return $this;
    }

    public function removeNode(string $name): DistributionHashingInterface
    {
        if (isset($this->serverList[$name])) {
            // 删除虚拟节点
            foreach ($this->serverList[$name] as $v) {
                unset($this->virtualNode[$v]);
            }

            // 删除真实节点
            unset($this->serverList[$name]);
        }

        return $this;
    }

    public function getNode(string $name): string
    {
        $hashValue = $this->hashing($name);
        /**
         * 先赋值为第一个虚拟节点。
         * current为当前元素，不一定是第一个，虽然默认是第一个。
         * reset将数组指针移动到第一个并返回单元的值。
         * 虚拟节点对应真实节点
         */
        $node = reset($this->virtualNode);
        foreach($this->virtualNode as $k => $v){
            if($k >= $hashValue){
                $node = $v;
                break;
            }
        }
        // 真实节点
        return $node;
    }

    public function hashing(string $str): string
    {
        $str = md5($str);
        return sprintf('%u', crc32($str));
    }

    public function getServerList(){
        return array_keys($this->serverList);
    }
}