<?php

namespace App\Http\Controllers\PDF;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\pdfs;

class IndexController extends Controller
{
    public $linebreak;

    public function index(Request $request, $id, $code, $birth, $encode)
    {
        $this->checkedCode($encode, $code);

        // PFS用のチャートグラフを生成するよう
        require_once(public_path()."/PDF/pfsCreateGraph.php");
        $obj = new pdfs();
        $pdf = $obj->addPageToPdf($id, $code, $birth);

        $filename = $code . "_" . date('Y') . date('m') . date('d') . ".pdf";
        return $pdf->Output($filename, 'D');

    }
    // 証明書ダウンロード
    public function certificate(Request $request, $id, $code, $birth, $encode)
    {
        $this->checkedCode($encode, $code);
        $obj = new pdfs("L");
        $pdf = $obj->addCeartficateToPdf($id, $code, $birth, $encode);
        $filename = $code . "_" . date('Y') . date('m') . date('d') . ".pdf";
        return $pdf->Output($filename, 'D');
    }

    public function checkedCode($encode, $code)
    {
        $passwd = config('const.consts.PASSWORD');
        $key = $passwd['key16'];
        $iv  = $passwd['iv'];
        $code_base64 = $this->fromBase64Url($encode);
        $decrypted = openssl_decrypt($code_base64, 'AES-128-CBC', $key, 0, $iv);
        if ($decrypted != $code) {
            echo "error";
            exit();
        }
        return true;
    }
    public function fromBase64Url($base64url)
    {
        $base64 = strtr($base64url, '-_', '+/');
        $pad = strlen($base64) % 4;
        if ($pad) {
            $base64 .= str_repeat('=', 4 - $pad);
        }
        return $base64;
    }


}
