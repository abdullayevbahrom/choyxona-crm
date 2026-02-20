<?php

namespace App\Services;

use App\Models\ActivityLog;

class ActivityLogQueryService
{
    public function build(array $filters)
    {
        $query = ActivityLog::query()->with('user')->latest('id');

        if (! empty($filters['action'])) {
            $query->where('action', 'like', '%'.$filters['action'].'%');
        }

        if (! empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (! empty($filters['subject_type'])) {
            $query->where('subject_type', $filters['subject_type']);
        }

        if (! empty($filters['subject_id'])) {
            $query->where('subject_id', $filters['subject_id']);
        }

        if (! empty($filters['date_from'])) {
            $query->where(
                'created_at',
                '>=',
                $filters['date_from'].' 00:00:00',
            );
        }

        if (! empty($filters['date_to'])) {
            $query->where(
                'created_at',
                '<=',
                $filters['date_to'].' 23:59:59',
            );
        }

        return $query;
    }
}
