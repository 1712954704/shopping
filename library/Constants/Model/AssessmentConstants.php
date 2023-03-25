<?php
/**
 * 考评配置
 * User: Jack
 * Date: 2023/03/14
 */

namespace library\Constants\Model;

class AssessmentConstants extends ModelConstants
{

    /**
     * 通用常量 1=正常 2=审核中 3=审核结束
     */

    // 状态 - 审核中
    const STATUS_CHECK_IN = 2;

    // 状态 - 审核结束
    const STATUS_CHECK_END = 3;

}
