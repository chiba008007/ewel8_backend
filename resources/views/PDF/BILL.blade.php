<!doctype html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<title>請求書</title>
<style>
    body {
        font-family: ipaexm;
        font-size: 12px;
    }
    .cols {
        display: inline-block;
        width: 45%;
        text-align: left;
    }
    .mt-1{ margin-top:10px;}
    .mt-2{ margin-top:20px;}
    .mt-4{ margin-top:40px;}
    .mt-6{ margin-top:60px;}
    .float{
        float: left;
        width: 45%;
    }
    .right{
        float: right;
        width: 30%;
    }
    .p2{ padding:2px; }
    .pt-1{ padding-top:5px; }
    .f8{ font-size:8px; }
    .f14{ font-size:14px; }
    .f18{ font-size:18px; }
    .bold{ font-weight:bold; }
    .text-left{ text-align: left;}
    .text-right{text-align: right;}
    .text-center{text-align: center;}
    .bb{border-bottom:1px solid #000;}
    .w30{ width:30px; }
    .w60{ width:60px; }
    .w100{ width:100px; }
    .w200{ width:200px; }
    .table{
        width:100%;
        border-collapse: collapse;
        border-spacing: 0;
    }
    .list{
        border-top:1px solid #000;
        border-left:1px solid #000;
    }
    .list td{
        border-right:1px solid #000;
        border-bottom:1px solid #000;
        text-align: center;
    }
    .list td.text-right{
        text-align: right;
    }
    .purple{
        background-color:rgb(239, 220, 247);
    }
</style>
</head>
<body>
    @if ($bill->tanto_print_flag)
    <div style="position: absolute; top:385px; left:720px;" >
        <img src="{{ public_path('images/logo/sasaki.gif') }}" style="height: 40px;" />
    </div>
    @endif
    @if ($bill->company_print_flag)
    <div style="position: absolute; top:200px; left:650px;">
        <img src="{{ public_path('images/logo/innovation.gif') }}" style="height: 60px;" />
    </div>
    @endif
    <div style="font-size: 18pt; text-align:center;">御請求書</div>
    <div class="mt-2">
        <div class="float" >
            〒{{ $bill->post }}
            {{ $bill->address_1 }}<br />
            {{ $bill->address_2 }}
            <p class="mt-2">{{ $bill->company_name }}</p>
            <div class="mt-2">{{ $bill->busyo }}</div>
            <div class="bb f14">
                {{ $bill->yakusyoku }}&nbsp;
                {{ $bill->name }} 様
            </div>
        </div>
        <div class="right text-right" >
            <div class="mt-2 text-right">
                <div class="bb text-left w200">
                    請求No.{{ $bill->bill_number }}
                </div>
                <div class="text-right w200">
                    {{ $bill->bill_date->format('Y年m月d日') }}
                </div>
            </div>
        </div>
    </div>
    <div style="clear:both">
        <div class="float mt-4" >
            ※ 下記の通りご請求申し上げます<br />
            <table class="table">
                <tr>
                    <td class="bold bb f14 p2">請求金額</td>
                    <td class="bold bb text-right p2 f18"> ¥ {{ number_format($bill->money) }}-</td>
                    <td class="bold f8 p2">消費税込</td>
                </tr>
            </table>
            <table class="table mt-1">
                <tr>
                    <td class="bb">件名:</td>
                    <td class="bb p2">{{ $bill->title }}</td>
                </tr>
                <tr>
                    <td class="bb pt-1">御支払日:</td>
                    <td class="bb p2 pt-1">{{ $bill->pay_date->format('Y年m月d日') }}</td>
                </tr>
                <tr>
                    <td class="bb pt-1">御振込先:</td>
                    <td class="bb p2 pt-1">{{ $bill->pay_bank }} (口座番号){{ $bill->pay_number }}</td>
                </tr>
                <tr>
                    <td class="bb pt-1">口座名義:</td>
                    <td class="bb p2 pt-1">{{ $bill->pay_name }}</td>
                </tr>
            </table>
            <div class="mt-1">※ 振込手数料は、貴社負担にてお願い申し上げます。</div>
        </div>
        <div class="right text-right" >
            <div style="width:300px;" class="text-left">
                <div>〒{{ $bill->from_post }}</div>
                <div>{{ $bill->from_address_1 }}</div>
                <div>{{ $bill->from_address_2 }}</div>
                <div>{{ $bill->from_name }}</div>
                <div class="mt-1">TEL:{{ $bill->from_tel }}</div>
            </div>
            <div style="width:60px;margin-left:160px;margin-top:30px;" class="text-right">
                <div style="border:1px solid #000;border-bottom:none;" class="text-center">担当者</div>
                <div style="border:1px solid #000;height:50px;"></div>
            </div>
        </div>
    </div>
    <div style="clear:both">
        <table class="table list mt-2">
            <tr>
                <td class="purple w30">No</td>
                <td class="purple">品名</td>
                <td class="purple">銘柄</td>
                <td class="purple">規格</td>
                <td class="purple w30">数量</td>
                <td class="purple w30">単位</td>
                <td class="purple w60">単価</td>
                <td class="purple w60">金額</td>
            </tr>
            @foreach ($bill->lists as $i => $list)
                @php
                    $even =  ($i % 2 === 1)?"purple":"";
                @endphp
                <tr>
                    <td class="{{ $even }} text-left">{{ $list->number}}</td>
                    <td class="{{ $even }} text-left">{{ $list->title}}</td>
                    <td class="{{ $even }} text-left">{{ $list->name}}</td>
                    <td class="{{ $even }} text-left">{{ $list->kikaku}}</td>
                    <td class="{{ $even }} text-left">{{ $list->quantity}}</td>
                    <td class="{{ $even }} text-left">{{ $list->unit}}</td>
                    <td class="{{ $even }} text-right">{{ number_format($list->money)}}</td>
                    <td class="{{ $even }} text-right">{{ number_format($list->quantity * $list->money) }}</td>
                </tr>
            @endforeach
            @php
                $blankRows = max($minRows - $rowCount, 0);
                if ($rowCount >= $minRows) {
                    $blankRows += $extraRows;
                }
            @endphp

            @for ($i = 0; $i < $blankRows; $i++)
                @php
                    $even = (($i + $rowCount) % 2 === 1) ? "purple" : "";
                @endphp
                <tr>
                    <td class="{{ $even }} ">{{ $i + $rowCount + 1 }}</td>
                    <td class="{{ $even }} ">&nbsp;</td>
                    <td class="{{ $even }} ">&nbsp;</td>
                    <td class="{{ $even }} ">&nbsp;</td>
                    <td class="{{ $even }} ">&nbsp;</td>
                    <td class="{{ $even }} ">&nbsp;</td>
                    <td class="{{ $even }} ">&nbsp;</td>
                    <td class="{{ $even }} ">&nbsp;</td>
                </tr>
            @endfor

            <tr>
                <td class="text-right" colspan="7">10%対象合計</td>
                <td class="text-right">{{ number_format($exTotal) }}</td>
            </tr>
            <tr>
                <td class="text-right" colspan="7">消費税({{ $tax }}%)</td>
                <td class="text-right">{{ number_format($taxTotal) }}
            </tr>
            <tr>
                <td class="text-right f18" colspan="7">合計(税込み)</td>
                <td class="text-right">{{ number_format($total) }}
            </tr>
        </table>
        <div>
            ※ 備考
            <div>{{ $bill->note }}</div>
        </div>
    </div>
    @include("PDF.FOOTER")
</body>
</html>
