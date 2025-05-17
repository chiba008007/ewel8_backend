<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Exam;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class csvUploadController extends Controller
{
    public function updateCsvExam(Request $request)
    {
        $file = $request->file('file');
        // アップロードファイルのバックアップ
        // 任意でファイル名を生成（元のファイル名をそのまま使うなら）
        $fileName = $file->getClientOriginalName();

        // public/uploads に移動（保存）
        $file->move(public_path('uploads'), $fileName);

        // 保存先のパス（必要ならログなどに使える）
        $savedPath = 'uploads/' . $fileName;

        Log::info("ファイル保存成功: " . $savedPath);


        $user_id = $request->input('user_id');
        $test_id = $request->input('test_id');
        $originalName = $file->getClientOriginalName();
        $passwd = config('const.consts.PASSWORD');
        Log::info('CSVアップデート開始:'.$originalName);
        DB::beginTransaction();
        try {
            if (!$this->checkuser($user_id)) {
                throw new Exception();
            }
            if (!$file) {
                Log::info('アップデート用ファイルが選択されていない');
                throw new Exception();
            }
            // CSVの内容を読みたい場合
            $csv = array_map('str_getcsv', file($file->getRealPath()));
            $header = array_map('trim', $csv[0]);
            $rows = array_slice($csv, 1);

            // 対象データを取得（順番保証）
            $dbRows = Exam::where('customer_id', $user_id)
                ->where('test_id', $test_id)
                ->orderBy('id') // 昇順に固定
                ->get()
                ->values();

            if (count($rows) != $dbRows->count()) {
                Log::info('CSVと更新データの数が合わない:'.$originalName);
                throw new Exception();
            }

            $chunks = $dbRows->chunk(100);
            $rowIndex = 0;

            foreach ($chunks as $chunk) {
                $ids = [];
                $bindings = [];
                $bindingsname = [];
                $bindingskana = [];
                $bindingspassword = [];
                $bindingsmemo1 = [];
                $bindingsmemo2 = [];

                $caseSqlParts = [
                    'email' => "email = CASE id",
                    'name'  => "name = CASE id",
                    'kana'  => "kana = CASE id",
                    'password'  => "password = CASE id",
                    'memo1'  => "memo1 = CASE id",
                    'memo2'  => "memo2 = CASE id",
                ];

                foreach ($chunk as $row) {
                    $id = $row->id;
                    $ids[] = $id;

                    // BOM除去 + トリム
                    $emailVal = preg_replace('/^\xEF\xBB\xBF/', '', trim($rows[$rowIndex][1] ?? ''));
                    $nameVal  = preg_replace('/^\xEF\xBB\xBF/', '', trim($rows[$rowIndex][2] ?? ''));
                    $kanaVal  = preg_replace('/^\xEF\xBB\xBF/', '', trim($rows[$rowIndex][3] ?? ''));
                    $pwd = openssl_encrypt($rows[$rowIndex][4], 'aes-256-cbc', $passwd['key'], 0, $passwd['iv']);
                    $passwordVal  = preg_replace('/^\xEF\xBB\xBF/', '', trim($pwd ?? ''));
                    $memo1Val  = preg_replace('/^\xEF\xBB\xBF/', '', trim($rows[$rowIndex][5] ?? ''));
                    $memo2Val  = preg_replace('/^\xEF\xBB\xBF/', '', trim($rows[$rowIndex][6] ?? ''));

                    $caseSqlParts['email'] .= " WHEN ? THEN ?";
                    $bindings[] = $id;
                    $bindings[] = $emailVal;

                    $caseSqlParts['name'] .= " WHEN ? THEN ?";
                    $bindingsname[] = $id;
                    $bindingsname[] = $nameVal;

                    $caseSqlParts['kana'] .= " WHEN ? THEN ?";
                    $bindingskana[] = $id;
                    $bindingskana[] = $kanaVal;

                    $caseSqlParts['password'] .= " WHEN ? THEN ?";
                    $bindingspassword[] = $id;
                    $bindingspassword[] = $passwordVal;

                    $caseSqlParts['memo1'] .= " WHEN ? THEN ?";
                    $bindingsmemo1[] = $id;
                    $bindingsmemo1[] = $memo1Val;

                    $caseSqlParts['memo2'] .= " WHEN ? THEN ?";
                    $bindingsmemo2[] = $id;
                    $bindingsmemo2[] = $memo2Val;

                    $rowIndex++;
                }

                foreach ($caseSqlParts as &$part) {
                    $part .= " END";
                }

                $sql = "UPDATE exams SET " . implode(", ", $caseSqlParts) .
                    " WHERE id IN (" . implode(",", array_fill(0, count($ids), '?')) . ")";
                // email → name → WHERE句（id） の順にバインドされている
                $bindings = array_merge($bindings, $bindingsname);
                $bindings = array_merge($bindings, $bindingskana);
                $bindings = array_merge($bindings, $bindingspassword);
                $bindings = array_merge($bindings, $bindingsmemo1);
                $bindings = array_merge($bindings, $bindingsmemo2);
                $bindings = array_merge($bindings, $ids);

                Log::info("SQL: $sql");
                Log::info("bindings: " . json_encode($bindings));
                Log::info("バインド数: " . count($bindings));
                Log::info("プレースホルダ数: " . substr_count($sql, '?'));

                DB::statement($sql, $bindings);
            }

            DB::commit();
            return response(true, 200);
        } catch (Exception $e) {
            DB::rollBack();
            return response([], 400);
        }

    }
    //
    public function csvUploadFile(Request $request)
    {

        $user_id = $request->user_id;
        $test_id = $request->test_id;
        $passwd = config('const.consts.PASSWORD');
        try {
            if (!$this->checkuser($user_id)) {
                throw new Exception();
            }
            $result = Exam::select(['email','name','kana','password', 'memo1', 'memo2' ])
            ->where([
                'customer_id' => $user_id,
                'test_id' => $test_id,
            ])
            ->whereNull('deleted_at')
            ->orderby("id")
            ->get();
            $list = [];
            $no = 0;
            foreach ($result as $value) {
                $pwd = openssl_decrypt($value[ 'password' ], 'aes-256-cbc', $passwd['key'], 0, $passwd['iv']);
                $list[$no][ 'birth' ] = $pwd !== 'password' ? $pwd : '';
                $list[$no][ 'email' ] = $value[ 'email'];
                $list[$no][ 'name'  ] = $value[ 'name'  ] == null ? '' : $value[ 'name'];
                $list[$no][ 'kana'  ] = $value[ 'kana'  ] == null ? '' : $value[ 'kana'];
                $list[$no][ 'memo1' ] = $value[ 'memo1' ] == null ? '' : $value[ 'memo1'];
                $list[$no][ 'memo2' ] = $value[ 'memo2' ] == null ? '' : $value[ 'memo2'];

                $no++;
            }
            return response($list, 200);
        } catch (Exception $e) {
            return response([], 201);
        }
    }
}
