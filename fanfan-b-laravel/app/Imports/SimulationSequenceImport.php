<?php

namespace App\Imports;

use App\Models\simulation\RefSimulationSequence;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class SimulationSequenceImport implements ToModel, WithHeadingRow, ShouldQueue, WithChunkReading
{
  use Importable;
  public function model(array $row)
  {
    $columns = (new RefSimulationSequence)->getTableColumns(true);
    unset($columns[array_search('id', $columns)]);

    $conditions = [
      'home_score' => $row['home_score'],
      'away_score' => $row['away_score'],
      'scenario_no' => $row['scenario_no'],
      'seq' => $row['seq'],
    ];

    $updateData = [];
    foreach ($columns as $key => $val) {
      if (!isset($conditions[$key])) {
        $updateData[$val] = $row[$val];
      }
    }
    RefSimulationSequence::updateOrCreateEx($conditions, $updateData);
  }

  public function headingRow(): int
  {
    return 1;
  }

  public function chunkSize(): int
  {
    return 1000;
  }
}
