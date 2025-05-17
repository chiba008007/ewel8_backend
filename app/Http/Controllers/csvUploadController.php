<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Exam;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\csvuploads;
use Exception;

class csvUploadController extends Controller
{
    public function updateCsvExam(Request $request)
    {
        $string = "アップロード成功";
        $file = $request->file('file');
        $user_id = $request->input('user_id');
        $test_id = $request->input('test_id');
        $csvUploadType = $request->input('csvUploadType');
        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        // アップロードファイルのバックアップ
        $dummyfile = uniqid().".".$extension;
        // public/uploads に移動（保存）
        // コピー先のパス
        $destination = public_path('uploads/' . $dummyfile);
        // 一時ファイルのパス（アップロードされたファイルの一時保存場所）
        $source = $file->getRealPath();
        // コピー処理（移動じゃなくコピー！）
        copy($source, $destination);
        // 保存先のパス（必要ならログなどに使える）
        $savedPath = 'uploads/' . $dummyfile;
        Log::info("ファイル保存成功: " . $savedPath);

        $passwd = config('const.consts.PASSWORD');
        Log::info('CSVアップデート開始:'.$originalName);
        if ($csvUploadType == 1) {
            Log::info('CSVアップデート未受検者のみ:'.$originalName);
        } else {
            Log::info('CSVアップデート受検済み受検中:'.$originalName);
        }
        $updatedCount = 0;
        $totalrow = 0;
        $type = 0;
        $code = 200;
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
                ->wherenull('deleted_at');
            if ($csvUploadType == 1) {
                $dbRows = $dbRows->wherenull("started_at");
            }
            $dbRows = $dbRows->orderBy('id') // 昇順に固定
                ->get()
                ->values();
            // 未受検のみの時は
            // 全体データとの差分をとりcsvから削除する
            if ($csvUploadType == 1) {
                $dbRowsDefault = Exam::where('customer_id', $user_id)
                ->where('test_id', $test_id)
                ->wherenull('deleted_at')
                ->orderBy('id') // 昇順に固定
                ->get()
                ->values()
                ->map(function ($item, $index) {
                    $item->row_number = $index; // ← ここで連番を追加（0から）
                    return $item;
                });

                $dbIds = $dbRows->pluck('id')->toArray();

                $missingRowNumbers = $dbRowsDefault
                    ->filter(function ($item) use ($dbIds) {
                        return !in_array($item->id, $dbIds);
                    })
                    ->pluck('row_number')
                    ->values();
                // $rowsから$missingRowNumbersの行数のデータを外す
                $skipIndexes = $missingRowNumbers->toArray();
                // フィルターして除外行を削る
                $filteredRows = array_values(array_filter(
                    $rows,
                    function ($row, $index) use ($skipIndexes) {
                        return !in_array($index, $skipIndexes);
                    },
                    ARRAY_FILTER_USE_BOTH
                ));
                $rows = $filteredRows;
            }

            $totalrow = $dbRows->count();
            if (count($rows) != $dbRows->count()) {
                $string = "CSVと更新データの数が合わない";
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
                    $updatedCount++;
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

                if (DB::statement($sql, $bindings) === false) {
                    throw new Exception();
                }
            }

            $type = 1;
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            $type = 2;
            $code = 400;

        }
        // csvuploadテーブルに登録
        csvuploads::create([
            'test_id' => $test_id,
            'customer_id' => $user_id,
            'filename' => $dummyfile,
            'filepath' => $originalName,
            'type' => $type,
            'total' => $totalrow,
            'notrows' => $totalrow - $updatedCount,
            'memo' => $string
        ]);
        if ($code == 200) {
            return response(true, $code);
        } else {
            return response([], $code);
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

    public function getCsvUploadList(Request $request)
    {
        $user_id = $request->user_id;
        $test_id = $request->test_id;
        if (!$this->checkuser($user_id)) {
            throw new Exception();
        }

        $list = csvuploads::where([
            'status' => 1,
            'test_id' => $test_id,
            'customer_id' => $user_id,
        ])
        ->selectRaw('*, DATE_FORMAT(created_at, "%Y年%m月%d日") as date')
        ->get();
        return response($list, 200);
    }
}
