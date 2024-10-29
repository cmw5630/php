<?php

namespace App\Libraries\Classes;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Events\BeforeSheet;

class HImport1 implements ToCollection, WithHeadingRow, WithEvents
{
    public $sheetNames;
    public $sheetData;
    public function __construct()
    {
        $this->sheetNames = [];
        $this->sheetData = [];
    }

    public function collection(Collection $collection)
    {
        $this->sheetData[] = $collection;
    }

    public function registerEvents(): array
    {
        return [
            BeforeSheet::class => function (BeforeSheet $event) {
                $this->sheetNames[] = $event->getSheet()->getDelegate()->getTitle();
            }
        ];
    }
}
