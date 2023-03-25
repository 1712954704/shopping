<?php
/**
 * 审批配置
 * User: Jack
 * Date: 2023/03/07
 */

namespace library\Constants\Model;

class ApprovalConstants extends ModelConstants
{

    /**
     * 通用常量 1=发起审核 2=通过 3=拒绝 4=转审 5=审批驳回 6审核撤回 7=审核结束 默认为1通过
     */
    // 状态 - 发起审核
    const STATUS_START = 1;

    // 状态 - 通过审核
    const STATUS_PASS = 2;

    // 状态 - 拒绝审核
    const STATUS_REJECT = 3;

    // 状态 - 转移审核
    const STATUS_SHIFT = 4;

    // 状态 - 审核驳回
    const STATUS_CHECK_REFUTE = 5;

    // 状态 - 审核撤回
    const STATUS_REVOCATION = 6;

    // 状态 - 审核结束
    const STATUS_END = 7;

    // 流程节点 -1=自身修改审核
    const NODE_SELF_AGAIN = -1;

    // 流程节点 1=发起审批由主管/经理/公司负责人审核
    const NODE_SHIFT = 1;

    // 流程节点 2=主管审核
    const NODE_MANAGER = 2;

    // 流程节点 3=经理审核
    const NODE_HANDLER = 3;

    // 流程节点 4=公司负责人审核
    const NODE_DUTY = 4;

    // 流程节点 5=自评
    const NODE_SELF = 5;

    // 流程节点 6=主管审核
    const NODE_MANAGER_AGAIN = 6;

    // 流程节点 7=经理审核
    const NODE_HANDLER_AGAIN = 7;

    // 流程节点 8=公司负责人审核
    const NODE_DUTY_AGAIN = 8;

    // 流程节点 9=hr审核
    const NODE_HR = 9;

    // 流程节点 10=审核结束
    const NODE_END = 10;

    // 审核者类型 1=部门主管
    const APPROVAL_USER_TYPE_MANAGER = 1;

    // 审核者类型 2=部门经理
    const APPROVAL_USER_TYPE_HANDLER = 2;

    // 审核者类型 3=公司负责人
    const APPROVAL_USER_TYPE_DUTY = 3;

    // 审核者类型 4=hr
    const APPROVAL_USER_TYPE_HR = 4;

    // 审核者类型 5=自身审核
    const APPROVAL_USER_TYPE_SELF = 5;

    // 审核者类型 6=无人审核
    const APPROVAL_USER_TYPE_NO_BODY = 6;

    // 节点名称map
    const NODE_MAP = [
        self::NODE_SELF_AGAIN => '员工自检',
        self::NODE_SHIFT => '部门领导审批',
        self::NODE_MANAGER => '部门主管审核',
        self::NODE_HANDLER => '部门经理审核',
        self::NODE_DUTY => '公司负责人审核',
        self::NODE_SELF => '自评',
        self::NODE_MANAGER_AGAIN => '部门主管再次审核',
        self::NODE_HANDLER_AGAIN => '部门经理再次审核',
        self::NODE_DUTY_AGAIN => '公司负责人再次审核',
        self::NODE_HR => 'HR审核',
        self::NODE_END => '审核结束',
    ];

    // status map
    const STATUS_MAP = [
        self::STATUS_START => '审核中',
        self::STATUS_PASS => '审核通过',
        self::STATUS_REJECT => '审核拒绝',
        self::STATUS_SHIFT => '审核转移',
        self::STATUS_CHECK_REFUTE => '审核驳回',
        self::STATUS_REVOCATION => '审核撤回',
        self::STATUS_END => '审核结束'
    ];

}
