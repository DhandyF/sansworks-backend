<?php

namespace App\Services;

use App\Models\Repair;
use App\Models\RepairDeposit;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

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
            $query->whereHas('repair', fn($q) => $q->where('name', 'LIKE', "%{$search}%"));
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
        
        if (!empty($data['deposit_date']) && $repair->deadline_date) {
            $depositDate = Carbon::parse($data['deposit_date']);
            $deadlineDate = Carbon::parse($repair->deadline_date);
            
            if ($depositDate->gt($deadlineDate)) {
                $daysDelay = $depositDate->diffInDays($deadlineDate, true);
            }
            
            $totalValue = $repair->sewing_price * $data['total_deposit'];
            
            if ($daysDelay >= 1 && $daysDelay <= 3) {
                $chargePercent = 10;
                $chargeAmount = $totalValue * 0.10;
            } elseif ($daysDelay >= 4 && $daysDelay <= 10) {
                $chargePercent = 50;
                $chargeAmount = $totalValue * 0.50;
            } elseif ($daysDelay > 10) {
                $chargePercent = 100;
                $chargeAmount = $totalValue;
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
        
        if (!empty($data['deposit_date']) && $repair->deadline_date) {
            $depositDate = Carbon::parse($data['deposit_date']);
            $deadlineDate = Carbon::parse($repair->deadline_date);
            
            $daysDelay = 0;
            if ($depositDate->gt($deadlineDate)) {
                $daysDelay = $depositDate->diffInDays($deadlineDate, true);
            }
            
            $totalValue = $repair->sewing_price * ($data['total_deposit'] ?? $deposit->total_deposit);
            
            if ($daysDelay >= 1 && $daysDelay <= 3) {
                $chargePercent = 10;
                $chargeAmount = $totalValue * 0.10;
            } elseif ($daysDelay >= 4 && $daysDelay <= 10) {
                $chargePercent = 50;
                $chargeAmount = $totalValue * 0.50;
            } elseif ($daysDelay > 10) {
                $chargePercent = 100;
                $chargeAmount = $totalValue;
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