<!doctype html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<title>証明書</title>
<style type="text/css">
    .border{
        border:3px solid red;
    }
    .mt-30{
        margin-top:30px;
    }
    .mt-50{
        margin-top:50px;
    }
    .pb-30{
        padding-bottom:30px;
    }
    .pa-30{
        padding:30px;
    }
    .plt-10{
        padding-top:10px;
        padding-left:30px;
    }
    .plt-30{
        padding-top:30px;
        padding-left:30px;
    }
    .large-text-h2{
        font-size: 24px;
        font-weight: bold;
    }
    .large-text {
        font-size: 20px;
        font-weight: bold;
    }
</style>
</head>
<body>
    <div class="pa-30">
        <div class="pa-30 border">
            <h2 class="mt-30 large-text-h2" align="center" >受　検　証　明　書</h2>
            <div class="plt-30 large-text">
                受験番号：{{ $email }}
            </div>
            <div class="plt-10 large-text">
                氏名：{{ $exam->name }} 様
            </div>
            <div class="plt-30 large-text">
                下記の検査が完了したことを証明します。
            </div>
            <div class="plt-30 large-text">
                ■ 検査名
            </div>
            <div class="plt-10 large-text">
                検査 {{ $testname }}
            </div>
            <div class="plt-30 large-text">
                ■ 完了日：{{ optional($exam->ended_at)->format('Y年m月d日') }}
            </div>

            <h2 class="mt-50 large-text-h2" align="center" >{{ $exam->customer->name}}</h2>
            <h2 class="mt-30 large-text-h2" >受検証明書番号：{{ $number }}</h2>
        </div>
    </div>
    @include("PDF.FOOTER")
</body>
</html>
