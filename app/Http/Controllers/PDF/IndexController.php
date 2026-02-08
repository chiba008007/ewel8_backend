<?php

namespace App\Http\Controllers\PDF;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\pdfs;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Encryption\DecryptException;

class IndexController extends Controller
{
    public $linebreak;

    public function index(Request $request, $id, $code, $birth, $encode)
    {

//        abort(403, 'PDF上限エラー');
        Log::info('PDF@index called', compact('id', 'code', 'birth', 'encode'));
        try {
            $this->checkedCode($encode, $code);
            // PFS用のチャートグラフを生成するよう
            require_once(public_path()."/PDF/pfsCreateGraph.php");
            $obj = new pdfs();
            // pdfのダウンロードログを保存
            $pdf = $obj->addPageToPdf($id, $code, $birth);

            $filename = $code . "_" . date('Y') . date('m') . date('d') . ".pdf";
            return $pdf->Output($filename, 'D');

        } catch (DecryptException $e) {
            Log::error('DecryptException: '.$e->getMessage());
            abort(400, 'トークン復号エラー'); // ← 一旦 400 に
        } catch (\Throwable $e) {
            Log::error('PDF@index failed: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            abort(500, 'PDF生成エラー');
        }

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
