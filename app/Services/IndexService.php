<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class IndexService
{
    public function handlePagination($data)
    {
        return [
            'perPage' => $data->perPage(),
            'currentPage' => $data->currentPage(),
            "nextPage" => ($data->currentPage() >= $data->lastPage() ? null : $data->currentPage() + 1),
            "prevPage" => ($data->currentPage() <= 1 ? null : $data->currentPage() - 1),
            'lastPage' => $data->lastPage(),
            'from' => ($data->firstItem() === null) ? 0 : $data->firstItem(),
            'to' => ($data->lastItem() === null) ? 0 : $data->lastItem(),
            'total' => $data->total(),
            'pages' => $this->pages($data->currentPage(), $data->lastPage()),
        ];
    }

    private function pages($currentPage, $totalPages)
    {
        $maxVisiblePages = 5; 

        $startPage = max($currentPage - floor($maxVisiblePages / 2), 1);
        $endPage = min($startPage + $maxVisiblePages - 1, $totalPages);

        if ($endPage - $startPage + 1 < $maxVisiblePages) {
            $startPage = max($endPage - $maxVisiblePages + 1, 1);
        }

        $visiblePages = range($startPage, $endPage);

        if ($startPage > 1) {
            if($visiblePages[0] != 2)
                array_unshift($visiblePages, 2);
        }
        if ($endPage < $totalPages) {
            $visiblePages[] = $totalPages - 1;
            $visiblePages[] = $totalPages;
        }

        if($visiblePages[0] != 1){
            array_unshift($visiblePages, 1);
        }

        return $visiblePages;
    }


    public function limitPerPage($value)
    {
        return max(1, min(250, $value));
    }

    public function checkPageIfNull($value)
    {
        return ($value === null || !is_numeric($value)) ? 1 : $value;
    }

    public function checkIfSearchEmpty($value)
    {
        return (empty($value) ? null : $value);
    }

    public function checkIfEmpty($value)
    {
        return (empty($value) ? null : $value);
    }

    public function checkIfBoolEmpty($value)
    {
        if (empty($value)) {
            return null;
        }
        
        $lowercaseValue = strtolower($value);
        if ($lowercaseValue === 'true' || $lowercaseValue === '1' || $lowercaseValue === true || $lowercaseValue === 1) {
            return true;
        } elseif ($lowercaseValue === 'false' || $lowercaseValue === '0' || $lowercaseValue === false || $lowercaseValue === 0) {
            return false;
        }
        
        return null;
    }

    public function checkIfEnumHasValue($value, string $enumClass)
    {
        if (empty($value)) {
            return null;
        }
    
        $cases = $enumClass::cases();
        foreach ($cases as $case) {
            if ($case->value === $value) {
                return $value;
            }
        }
    
        return null;
    }

    public function checkValidDate($date)
    {
        try {
            $parsedDate = Carbon::parse($date);
            return $parsedDate->format('Y-m-d');
        } catch (\Exception $e) {
            return Carbon::today()->format('Y-m-d');
        }
    }
}