<?php

namespace App\Repositories;

use App\Models\Plan;

class PlanRepository
{
    public function findByPlanId(int $planId): Plan
    {
        return Plan::query()->findOrFail($planId);
    }
}
