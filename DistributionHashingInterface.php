<?php
/**
 * ----------------------------------------------------------
 * date: 2019/8/28 10:48
 * ----------------------------------------------------------
 * author: Raoxiaoya
 * ----------------------------------------------------------
 * describe: 分布式哈希算法接口
 * ----------------------------------------------------------
 */

interface DistributionHashingInterface
{
    /**
     * 添加节点服务器
     * @param string $name
     * @return string
     */
    function addNode(string $name): DistributionHashingInterface;

    /**
     * 移除节点服务器
     * @param string $name
     * @return string
     */
    function removeNode(string $name): DistributionHashingInterface;

    /**
     * 获取某个key应该存放在哪个节点
     * @param string $name
     * @return string
     */
    function getNode(string $name): string;

    /**
     * 哈希算法得到32位unsigned int
     * @param string $str
     * @return string
     */
    function hashing(string $str): string;
}