<?php

namespace App\Exports;

use App\Models\ActivityLog;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ActivityLogExport implements FromCollection, WithHeadings, WithMapping
{
    protected $logs;

    public function __construct($logs)
    {
        $this->logs = $logs;
    }

    public function collection()
    {
        return $this->logs;
    }

    public function map($log): array
    {
        return [
            $log->created_at->format('Y-m-d H:i:s'),
            $log->user ? $log->user->name : 'System',
            $log->module,
            $log->action,
            $log->description,
            $log->ip_address,
            $log->browser,
            $log->platform
        ];
    }

    public function headings(): array
    {
        return [
            'Time',
            'User',
            'Module',
            'Action',
            'Description',
            'IP Address',
            'Browser',
            'Platform'
        ];
    }
}
