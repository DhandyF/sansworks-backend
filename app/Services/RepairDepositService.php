<?php

namespace App\Services;

use App\Models\Repair;
use App\Models\RepairDeposit;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class RepairDepositService extends BaseService
{
    public function __construct(RepairDeposit $model)
    {
        $this->model = $model;
    }

    public function paginate(int $perPage = 15, string $search = null, string $tailorId = null): LengthAwarePaginator
    {
        $query = $this->model->with(['repair.tailor', 'repair.brand', 'repair.article', 'tailor']);

        if ($search) {
            $query->whereHas('repair', fn($q) => $q->where('name', 'LIKE', "%{$search}%")->orWhere(DB::raw("LOWER(name)"), 'LIKE', DB::raw("LOWER('%{$search}%')")));
        }

        if ($tailorId) {
            $query->where('tailor_id', $tailorId);
        }

        return $query->orderBy('deposit_date', 'desc')->paginate($perPage);
    }

    public function find(string $id)
    {
        return $this->model->with(['repair.tailor', 'repair.brand', 'repair.article', 'tailor'])->findOrFail($id);
    }

    public function create(array $data): RepairDeposit
    {
        $repair = Repair::findOrFail($data['repair_id']);

        $chargeAmount = 0;
        $chargePercent = 0;
        $daysDelay = 0;
        $defaultChargePerPcs = $data['default_charge_per_pcs'] ?? null;
        $depositQty = $data['total_deposit'];

        if (!empty($data['deposit_date']) && $repair->deadline_date) {
            $depositDate = Carbon::parse($data['deposit_date']);
            $deadlineDate = Carbon::parse($repair->deadline_date);

            if ($depositDate->gt($deadlineDate)) {
                $daysDelay = $depositDate->diffInDays($deadlineDate, true);
            }

            if ($daysDelay >= 1 && $daysDelay <= 3) {
                // Use default charge per pcs
                $chargeAmount = ($defaultChargePerPcs ?? 0) * $depositQty;
                $chargePercent = 0; // Not percentage-based anymore
            } elseif ($daysDelay >= 4 && $daysDelay <= 10) {
                // Double the default charge per pcs
                $chargeAmount = (($defaultChargePerPcs ?? 0) * 2) * $depositQty;
                $chargePercent = 0;
            } elseif ($daysDelay >= 11) {
                // 100% of sewing price (for 11+ days delay)
                $chargeAmount = $repair->sewing_price * $depositQty;
                $chargePercent = 100;
            } else {
                $chargeAmount = 0;
                $chargePercent = 0;
            }
        }

        $data['charge_amount'] = $chargeAmount;
        $data['charge_percent'] = $chargePercent;

        $deposit = $this->model->create($data);

        $this->updateRepairStatus($repair);

        return $deposit;
    }

    public function update(string $id, array $data): RepairDeposit
    {
        $deposit = $this->model->findOrFail($id);
        $oldData = $deposit->toArray();

        $repair = $deposit->repair;

        $chargeAmount = 0;
        $chargePercent = 0;
        $daysDelay = 0;
        $defaultChargePerPcs = $data['default_charge_per_pcs'] ?? $deposit->default_charge_per_pcs ?? null;
        $depositQty = $data['total_deposit'] ?? $deposit->total_deposit;

        if (!empty($data['deposit_date']) && $repair->deadline_date) {
            $depositDate = Carbon::parse($data['deposit_date']);
            $deadlineDate = Carbon::parse($repair->deadline_date);

            if ($depositDate->gt($deadlineDate)) {
                $daysDelay = $depositDate->diffInDays($deadlineDate, true);
            }

            if ($daysDelay >= 1 && $daysDelay <= 3) {
                // Use default charge per pcs
                $chargeAmount = ($defaultChargePerPcs ?? 0) * $depositQty;
                $chargePercent = 0; // Not percentage-based anymore
            } elseif ($daysDelay >= 4 && $daysDelay <= 10) {
                // Double the default charge per pcs
                $chargeAmount = (($defaultChargePerPcs ?? 0) * 2) * $depositQty;
                $chargePercent = 0;
            } elseif ($daysDelay >= 11) {
                // 100% of sewing price (for 11+ days delay)
                $chargeAmount = $repair->sewing_price * $depositQty;
                $chargePercent = 100;
            } else {
                $chargeAmount = 0;
                $chargePercent = 0;
            }
        }

        $data['charge_amount'] = $chargeAmount;
        $data['charge_percent'] = $chargePercent;

        $deposit->update($data);
        $updatedRecord = $deposit->fresh();

        $this->updateRepairStatus($repair);

        $this->logUpdate($oldData, $updatedRecord->toArray());

        return $updatedRecord;
    }

    public function getRemaining(string $repairId): array
    {
        $repair = Repair::findOrFail($repairId);
        $totalDeposited = $repair->deposits()->sum('total_deposit');
        
        return [
            'total_repair' => $repair->total_repair,
            'total_deposited' => $totalDeposited,
            'remaining' => $repair->total_repair - $totalDeposited,
        ];
    }

    public function delete(string $id): void
    {
        $deposit = $this->model->findOrFail($id);
        $repair = $deposit->repair;
        $recordData = $deposit->toArray();
        
        $deposit->delete();
        
        $this->updateRepairStatus($repair);
        
        $this->logDelete($recordData);
    }

    private function updateRepairStatus(Repair $repair): void
    {
        $totalDeposited = $repair->deposits()->sum('total_deposit');
        
        if ($totalDeposited >= $repair->total_repair) {
            $repair->update(['status' => 'done']);
        } elseif (now()->gt($repair->deadline_date) && $totalDeposited < $repair->total_repair) {
            $repair->update(['status' => 'overdue']);
        } else {
            $repair->update(['status' => 'in_progress']);
        }
    }
}