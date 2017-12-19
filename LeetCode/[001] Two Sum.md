## Two Sum

* Description
> Given an array of integers, return indices of the two numbers such that they add up to a specific target.
> You may assume that each input would have exactly one solution,and you may not use the same element twice.

* Example:
```
Given nums = [2, 7, 11, 15], target = 9,

Because nums[0] + nums[1] = 2 + 7 = 9,
return [0, 1].
```

---

* **@Python**
> 计算列表长度。
> 使用双重循环遍历下标，判断列表nums中的每一个元素数据nums[i]和nums[j]的元素相加是否等于目标target。
> 时间复杂度为O(n^2)，被称为穷举法。

```
def twoSum(nums, target):
    """
    @summary: Element index that meets the number of targets
    @param nums: {list} List of integers
    @param target: {int} target value，the sum of the two num
    @return: {list} [index, index] Index of two numbers
    @note: not use the same element twice
    @see: https://leetcode.com/problems/two-sum/description/
    """
    for i in xrange(len(nums)):
        for j in xrange(i + 1, len(nums)):
            if nums[i] + nums[j] == target:
                return [i, j]
    return [0, 0]
```

* **@Python**
> 使用字典dict在查找key时使用的是hashmap的方式，查找速度较快。
> 定义新的缓冲区，可以得到“空间换时间”的效果，时间复杂度为O(n)。

```
def twoSum(nums, target):
    """
    @summary: Element index that meets the number of targets
    @param nums: {list} List of integers
    @param target: {int} target value
    @return: {list} [index, index] Index of two numbers
    @note: not use the same element twice
    @see: https://leetcode.com/problems/two-sum/description/
    """
    buff_dict = {}
    for i in range(len(nums)):
        if nums[i] in buff_dict:
            return [buff_dict[nums[i]], i]
        buff_dict[target - nums[i]] = i
    return [0, 0]
```

* **@C**
> 与Python的穷举法思路一致。

```
#include <stdio.h>
#include <malloc.h>

int *twoSum(int *nums, int target, int numsSize)
{
    int i, j;

    /* 分配8个字节的内存空间，用来存放数组索引值 */
    int *result = (int *)malloc(2 * sizeof(int));
    if(buffer==NULL) exit(1);   // 判断是否分配成功

    for (i = 0; i < numsSize; i++)
    {
        for (j = i + 1; j < numsSize; j++)
        {
            if (nums[i] + nums[j] == target) {
                result[0] = i;
                result[1] = j;
            }
        }
    }
    return result;
}

int main(void)
{
    int target = 17;
    int nums[] = {2, 7, 11, 15};
    int length = sizeof(nums) / sizeof(nums[0]);  // 只能在数组定义时获取数组长度
    int *result = twoSum(nums, target, length);

    printf("[%d, %d]\n", result[0], result[1]);
    return 0;
}
```
