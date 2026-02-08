<?php

namespace App\Libraries;

use App\Models\Test;
use App\Models\PdfHistory;

class PdfCountLimit
{
    public function pdfCountLimitCheck($test_id)
    {
        $exam = Test::where([
            ['id', '=', $test_id],
        ])->first();

        $count = PdfHistory::where([
            ['test_id',$test_id]
        ])->count();

        $pdfcountflag = $exam->pdfcountflag;
        $pdflimitcount = $exam->pdflimitcount;
        if($pdfcountflag == 1 && $count > $pdflimitcount){

            return false;
        }
        return true;
    }
}
