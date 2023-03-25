<?php
namespace App\Http\Manager\Hr;

use App\Http\Manager\ManagerBase;
use CacheConstants;
class ApprovalManager extends ManagerBase
{
    /**
     * 审核锁
     *
     * @param $assessments_id
     *
     * @return bool|int
     * @throws \Exception
     */
    public function lock_approval($assessments_id) {
        return $this->lock(sprintf(CacheConstants::EHR_APPROVAL_LOCK, $assessments_id), 10);
    }

    /**
     * 审核解锁
     *
     * @param $assessments_id
     *
     * @return int
     */
    public function unlock_approval($assessments_id) {
        return $this->unlock(sprintf(CacheConstants::EHR_APPROVAL_LOCK, $assessments_id));
    }


}
