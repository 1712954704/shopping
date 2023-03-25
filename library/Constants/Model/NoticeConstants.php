<?php
/**
 * 用户配置
 * User: Jack
 * Date: 2023/03/07
 */

namespace library\Constants\Model;

class NoticeConstants extends ModelConstants
{

    /**
     * 通用常量
     */
    // 状态 - 暂存
    const COMMON_STATUS_WAIT = 1;
    // 状态 - 发布
    const COMMON_STATUS_PUSH = 2;
    // 通知类型 1=通知
    const COMMON_TYPE_MESSAGE = 1;
    // 通知类型 2=公告
    const COMMON_TYPE_AFFICHE = 2;
}
