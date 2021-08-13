package main

import (
	"crypto/md5"
	"fmt"
	"hash/crc32"
	"sort"
	"strconv"
)

var nodes = []string{
	"server-0",
	"server-1",
	"server-2",
	"server-3",
	"server-4",
	"server-5",
	"server-6",
	"server-7",
	"server-8",
	"server-9",
}

func main() {

	/**************************************************/

	d := NewDistribution(nodes, 20)
	showNodeDetail(d)
	d = NewDistribution(nodes, 100)
	showNodeDetail(d)
	d = NewDistribution(nodes, 150)
	showNodeDetail(d)
	d = NewDistribution(nodes, 300)
	showNodeDetail(d)
	d = NewDistribution(nodes, 400)
	showNodeDetail(d)
	d = NewDistribution(nodes, 500)
	showNodeDetail(d)

	/*
		virtualNum: 20, gap: 1245
		virtualNum: 100, gap: 1160
		virtualNum: 150, gap: 1111
		virtualNum: 300, gap: 1120
		virtualNum: 400, gap: 1090
		virtualNum: 500, gap: 1066

		1w的key，分布到10个节点中，每个节点上分布的key的个数差别不算大，而且随着虚拟节点个数的增加，差距越小。
	*/

	/**************************************************/
	d = NewDistribution(nodes, 20)
	showNodeSet(d)

	fmt.Println("新增一个节点：")
	d.AddNode("server-10")
	showNodeSet(d)

	fmt.Println("移除一个节点：")
	d.RemoveNode("server-1")
	showNodeSet(d)

	/**
	good-0 --> server-7
	good-1 --> server-1
	good-2 --> server-2
	good-3 --> server-3
	good-4 --> server-1
	good-5 --> server-8
	good-6 --> server-7
	good-7 --> server-6
	good-8 --> server-8
	good-9 --> server-2
	新增一个节点：
	good-0 --> server-7
	good-1 --> server-1
	good-2 --> server-2
	good-3 --> server-3
	good-4 --> server-1
	good-5 --> server-8
	good-6 --> server-7
	good-7 --> server-10
	good-8 --> server-8
	good-9 --> server-2
	移除一个节点：
	good-0 --> server-7
	good-1 --> server-10
	good-2 --> server-2
	good-3 --> server-3
	good-4 --> server-6
	good-5 --> server-8
	good-6 --> server-7
	good-7 --> server-10
	good-8 --> server-8
	good-9 --> server-2

	可见新增还是移除节点，数据重新分配的比例还是挺少的。

	*/
	/**************************************************/
}

/*********** 分布均匀性测试 ***********/
func showNodeDetail(d *Distribution) {
	nodes := d.GetServerList()
	nodesCount := make(map[string]int)
	for _, v := range nodes {
		nodesCount[v] = 0
	}

	for i := 0; i < 10000; i++ {
		nodesCount[d.GetNode("good-"+strconv.Itoa(i))]++
	}

	var max, min int
	for _, v := range nodesCount {
		if v > max {
			max = v
		}

		if min == 0 {
			min = v
		} else if v < min {
			min = v
		}
	}

	fmt.Printf("virtualNum: %d, gap: %d \n", d.VirtualNum, max-min)
}

/*********** 颠覆性测试 ***********/
func showNodeSet(d *Distribution) {
	for i := 0; i < 10; i++ {
		key := "good-" + strconv.Itoa(i)
		fmt.Printf("%s --> %s\n", key, d.GetNode(key))
	}
}

///////////////////////////////////////////////////////////////////////////

type Distribution struct {
	// 真实节点服务器
	ServerList map[string][]uint32
	// 虚拟节点位置
	VirtualNode map[uint32]string
	// 用于排序
	VirtualNodeSlice []uint32
	// 每个真实节点拥有的虚拟节点个数
	VirtualNum int
}

func NewDistribution(nodes []string, virtualNum int) *Distribution {
	d := &Distribution{}
	if virtualNum > 0 {
		d.VirtualNum = virtualNum
	} else {
		d.VirtualNum = 1
	}

	sl := make(map[string][]uint32)
	vn := make(map[uint32]string)
	d.ServerList = sl
	d.VirtualNode = vn

	if len(nodes) > 0 {
		for _, v := range nodes {
			d.AddNode(v)
		}
	}

	return d
}

func (d *Distribution) AddNode(node string) {
	_, ok := d.ServerList[node]
	if ok {
		return
	}
	for i := 0; i < d.VirtualNum; i++ {
		hashValue := d.Hashing(node + "-" + strconv.Itoa(i))
		d.VirtualNode[hashValue] = node
		d.ServerList[node] = append(d.ServerList[node], hashValue)
		d.VirtualNodeSlice = append(d.VirtualNodeSlice, hashValue)
	}

	// 对虚拟节点从小到大排序
	sort.Sort(Uint32Slice(d.VirtualNodeSlice))
}

func (d *Distribution) RemoveNode(node string) {
	_, ok := d.ServerList[node]
	if !ok {
		return
	}

	// 删除虚拟节点
	for _, hashValue := range d.ServerList[node] {
		delete(d.VirtualNode, hashValue)
		for k, vs := range d.VirtualNodeSlice {
			if vs == hashValue {
				d.VirtualNodeSlice = append(d.VirtualNodeSlice[0:k], d.VirtualNodeSlice[k+1:]...)
			}
		}
	}

	// 删除真实节点
	delete(d.ServerList, node)
}

func (d *Distribution) GetNode(key string) string {
	hashValue := d.Hashing(key)
	/**
	 * 先赋值为第一个虚拟节点。
	 * 找到大于等于key哈希的最小的那个节点
	 */

	realNode := d.VirtualNode[d.VirtualNodeSlice[0]]
	for _, v := range d.VirtualNodeSlice {
		if v >= hashValue {
			realNode = d.VirtualNode[v]
			break
		}
	}

	return realNode
}

func (d *Distribution) Hashing(str string) uint32 {
	mstr := make([]byte, 0)
	m := md5.Sum([]byte(str))
	for _, v := range m {
		mstr = append(mstr, v)
	}
	return crc32.ChecksumIEEE(mstr)
}

func (d *Distribution) GetServerList() []string {
	l := make([]string, 0)
	for k, _ := range d.ServerList {
		l = append(l, k)
	}

	return l
}

type Uint32Slice []uint32

func (s Uint32Slice) Len() int { return len(s) }

func (s Uint32Slice) Swap(i, j int) { s[i], s[j] = s[j], s[i] }

func (s Uint32Slice) Less(i, j int) bool { return s[i] < s[j] }
