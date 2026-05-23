<?php

namespace App\Imports\Cement;

use Maatwebsite\Excel\Concerns\ToArray;

class CementRawImport implements ToArray
{
    public array $sheets = [];

    public function array(array $array)
    {
        $this->sheets[] = $array;
    }
}
