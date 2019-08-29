<?php
/**
 * ----------------------------------------------------------
 * date: 2019/8/28 14:11
 * ----------------------------------------------------------
 * author: Raoxiaoya
 * ----------------------------------------------------------
 * describe: 分布式哈希算法测试
 * ----------------------------------------------------------
 */

require 'DistributionHashingInterface.php';
require 'DistributionHashing.php';

$nodes = [
    'server-0',
    'server-1',
    'server-2',
    'server-3',
    'server-4',
    'server-5',
    'server-6',
    'server-7',
    'server-8',
    'server-9',
];

/*********** 分布均匀性测试 ***********/

function showNodeDetail(DistributionHashingInterface $model)
{
    $nodes      = $model->getServerList();
    $nodesCount = [];
    foreach ($nodes as $k => $v) {
        $nodesCount[$v] = 0;
    }

    for ($i = 0; $i < 10001; $i++) {
        $nodesCount[$model->getNode('good-' . $i)]++;
    }

    echo "virtualNum: " . $model->getVirtualNum() . ", gap: " . (max($nodesCount) - min($nodesCount)) . PHP_EOL;
    return $nodesCount;
}

// 虚拟节点：20
$model = new DistributionHashing($nodes);
showNodeDetail($model);
// 虚拟节点：100
$model = new DistributionHashing($nodes, 100);
showNodeDetail($model);
// 虚拟节点：150
$model = new DistributionHashing($nodes, 150);
showNodeDetail($model);
// 虚拟节点：300
$model = new DistributionHashing($nodes, 300);
showNodeDetail($model);

/**
    virtualNum: 20, gap: 489
    virtualNum: 100, gap: 307
    virtualNum: 150, gap: 269
    virtualNum: 300, gap: 167

 *  1w的key，分布到10个节点中，个数差别不算大，而且随着虚拟节点个数的增加，差距越小。
 */

echo '--------------------------------------------' . PHP_EOL;

/*********** 颠覆性测试 ***********/

$model = new DistributionHashing($nodes);

function showNodeSet(DistributionHashingInterface $model)
{
    for ($i = 0; $i < 10; $i++) {
        $key = 'good-' . $i;
        echo $key . ' ---> ' . $model->getNode($key) . PHP_EOL;
    }
}

// 默认节点
showNodeSet($model);

/**************************************************/
echo '新增一个节点：' . PHP_EOL;
$model = $model->addNode('server-10');
showNodeSet($model);

/**************************************************/
echo '移除一个节点：' . PHP_EOL;
$model = $model->removeNode('server-1');
showNodeSet($model);

/**
    good-0 ---> server-5
    good-1 ---> server-6
    good-2 ---> server-3
    good-3 ---> server-8
    good-4 ---> server-8
    good-5 ---> server-8
    good-6 ---> server-4
    good-7 ---> server-1
    good-8 ---> server-1
    good-9 ---> server-0
 *
    新增一个节点：
    good-0 ---> server-5
    good-1 ---> server-6
    good-2 ---> server-3
    good-3 ---> server-8
    good-4 ---> server-8
    good-5 ---> server-8
    good-6 ---> server-4
    good-7 ---> server-1
    good-8 ---> server-1
    good-9 ---> server-0
 *
    移除一个节点：
    good-0 ---> server-5
    good-1 ---> server-6
    good-2 ---> server-3
    good-3 ---> server-8
    good-4 ---> server-8
    good-5 ---> server-8
    good-6 ---> server-4
    good-7 ---> server-6
    good-8 ---> server-5
    good-9 ---> server-0

 * 可见新增还是移除节点，数据重新分配的比例还是挺少的。
 */