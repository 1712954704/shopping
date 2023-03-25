<?php
namespace library\Constants\Model;
/**
 * Model通用的相关常量
 *
 * Class ModelConstants
 */
class ModelConstants {
    // 状态 - 删除
    const COMMON_STATUS_DELETE = -1;
    // 状态 - 正常/显示
    const COMMON_STATUS_NORMAL = 1;

    // 排序类型 - 升序
    const ORDER_BY_TYPE_ASC = 1;
    // 排序类型 - 倒序
    const ORDER_BY_TYPE_DESC = 2;

    // 置顶标识 - 置顶
    const COMMON_FLAG_TOP = 1;
    // 置顶标识 - 不置顶
    const COMMON_FLAG_NOT_TOP = 0;

    // 数据库无符号int最大值
    const UNSIGNED_INT_MAX = 4294967296;
}
