<!doctype html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<title>PDF</title>
<style type="text/css">
    .table{
        border-top:1px solid #000;
        border-right:1px solid #000;
        padding:0px;
        margin:0px;
        width:100%;
        border-spacing:0
    }
    .table2{
        padding:0px;
        margin:0px;
        width:100%;
        border-spacing:0
    }
    .td{
        padding:3px;
        border-left:1px solid #000;
        border-bottom:1px solid #000;
        text-align:center;

    }
    .short{
        width:40px;
    }
    .min{
        font-size:10px;
    }
    .green{
        background-color:rgb(183, 236, 219);
    }
    .w-100{
        width:100%;
    }
    .mt-3{
        margin-top:3px;
    }
    .pa-3{
        padding:3px;
    }
    .pa-5{
        padding:10px;
    }
    .box{
        border:1px solid #000;
        width: 100%;
    }
    .min{
        font-size:12px;
    }
    .min2{
        font-size:8px;
    }
    .absolute{
        position: absolute;
        top:0;
        left:0;
    }
    .area1{
        border:1px solid #000;
        text-align: center;
    }
    .border{
        border:1px solid #000;
    }
    .ht{
        height:140px;
    }
    .ht2{
        height:160px;
    }
    .footer{
        position: absolute;
        bottom:0;
        right:0;
    }
    .bar{
        background-color:green;
        padding-right:3px;
        padding-bottom:2px;
    }
    .tate {
        background-color:red;
        width:24px;
        left:663;
    }
    .tate00{ top:469; height:1px;}
    .tate05{ top:455; height:15px;}
    .tate10{ top:440; height:30px;}
    .tate15{ top:425; height:45px;}
    .tate20{ top:413; height:56px;}
    .tate25{ top:399; height:70px;}
    .tate30{ top:384; height:86px;}
    .tate35{ top:370; height:100px;}
    .tate40{ top:356; height:113px;}
    .tate45{ top:342; height:127px;}
    .tate50{ top:327; height:142px;}
    .tate55{ top:312; height:157px;}
    .tate60{ top:299; height:171px;}
    .tate65{ top:287; height:183px;}
    .tate70{ top:272; height:198px;}
    .tate75{ top:257; height:213px;}
    .tate80{ top:242; height:228px;}
    .tate85{ top:228; height:242px;}
    .tate90{ top:214; height:256px;}
    .tate95{ top:199; height:271px;}
    .tate100{ top:184; height:286px;}
    .center{ text-align: center; }
</style>
</head>
<body>
    @if ($row > 0)
        <div style="page-break-before: always"></div>
    @endif
    <table style="width:100%;">
        <tr>
            <td width=300><img src="{{ public_path('images/PDF/welcome.jpg') }}" /></td>
            <td width=300 style="text-align:right;">
                <h2>パワハラ傾向振り返りシート</h2>
            </td>
        </tr>
    </table>
    <div style="padding:0px min">企業名:{{ $value->name }}企業</div>
    <table class="table min" >
        <tr>
            <td class="td green" >受検日</td>
            <td class="td">{{ $result->startdate }}</td>
            <td class="td green">受検ID</td>
            <td class="td">{{ $exam->email }}</td>
            <td class="td green">氏名</td>
            <td class="td" style="width:200px;">{{ $exam->name }}({{ $exam->kana }})</td>
            <td class="td green">年齢</td>
            <td class="td" >{{ $age }}</td>
        </tr>
    </table>
    <div class="mt-3 min">1.要注意や危険が３つ以上ある場合は自身の言動をしっかり振り返りましょう</div>
    <div class="mt-3">
        <img src="{{ public_path('images/PDF/pawahara.png') }}" width="100%" />
    </div>
    <div class="absolute bar" style="top:194;left:186;width:{{ $risk[1]['width'] }}px;">&nbsp;</div>
    <div class="absolute min" style="top:198;left:551;">{{ $risk[1]['point'] }}</div>
    <div class="absolute min center" style="top:198;left:594;width:50px;">{{ $risk[1]['text'] }}</div>
    <div class="absolute bar" style="top:240;left:186;width:{{ $risk[2]['width'] }}px;">&nbsp;</div>
    <div class="absolute min" style="top:244;left:551;">{{ $risk[2]['point'] }}</div>
    <div class="absolute min center" style="top:244;left:594;width:50px;">{{ $risk[2]['text'] }}</div>
    <div class="absolute bar" style="top:286;left:186;width:{{ $risk[3]['width'] }}px;">&nbsp;</div>
    <div class="absolute min" style="top:290;left:551;">{{ $risk[3]['point'] }}</div>
    <div class="absolute min center" style="top:290;left:594;width:50px;">{{ $risk[3]['text'] }}</div>
    <div class="absolute bar" style="top:346;left:186;width:{{ $risk[4]['width'] }}px;">&nbsp;</div>
    <div class="absolute min" style="top:350;left:551;">{{ $risk[4]['point'] }}</div>
    <div class="absolute min center" style="top:350;left:594;width:50px;">{{ $risk[4]['text'] }}</div>
    <div class="absolute bar" style="top:393;left:186;width:{{ $risk[5]['width'] }}px;">&nbsp;</div>
    <div class="absolute min" style="top:394;left:551;">{{ $risk[5]['point'] }}</div>
    <div class="absolute min center" style="top:394;left:594;width:50px;">{{ $risk[5]['text'] }}</div>
    <div class="absolute bar" style="top:439;left:186;width:{{ $risk[6]['width'] }}px;">&nbsp;</div>
    <div class="absolute min" style="top:441;left:551;">{{ $risk[6]['point'] }}</div>
    <div class="absolute min center" style="top:441;left:594;width:50px;">{{ $risk[6]['text'] }}</div>
    <div class="absolute tate tate{{ $risk['tate'] }}">&nbsp;</div>
    <div class="absolute min2" style="top:170;left:680;">({{ $risk[7]['point'] }}/10)</div>

    <div class="mt-3 min">2.パワハラリスクの全体傾向</div>
    <div class="mt-3 pa-5 box min ht">{{ $risk['pawahararisk'] }}</div>
    <table class="mt-3 min w-100">
        <tr>
            <td style="width:50%;">
                <div >3.一番留意すべき項目の傾向</div>
            </td>
            <td style="width:49%;margin-left:2%">
                <div >4.一番、留意すべき項目についての道しるべ(助言)</div>
            </td>
        </tr>
    </table>
    <table class="mt-3 min w-100 table2 ">
        <tr>
            <td style="width:49%;vertical-align:top;" class="border pa-5 ht2">
                <div class="ht2">{{ $risk['pattern'][4] }}</div>
            </td>
            <td>&nbsp;</td>
            <td style="width:49%;vertical-align:top;" class="border pa-5 ht2">
                <div class="ht2">{{ $risk['pattern'][5] }}</div>
            </td>
        </tr>
    </table>
    <table class="mt-3 min w-100">
        <tr>
            <td>
                <div >5.こんな言動に身に覚えがありませんか？</div>
            </td>
        </tr>
    </table>

    <table class="mt-3 min w-100 table2" >
        <tr>
            <td style="width:49%; text-align:center;" class="border pa-3" >
                ①{{ $risk[ 'pattern' ][ 'remember' ][1] }}
            </td>
            <td>&nbsp;</td>
            <td style="width:49%; text-align:center;" class="border pa-3" >
                <div >②{{ $risk[ 'pattern' ][ 'remember' ][2] }}</div>
            </td>
        </tr>
    </table>
    <table class="min w-100 table2 " style="margin-top:4px;">
        <tr>
            <td style="width:49%;vertical-align:top;" class="border pa-5 ht2">
                <div class="ht2">{!! nl2br($risk['pattern'][6]) !!}</div>
            </td>
            <td>&nbsp;</td>
            <td style="width:49%;vertical-align:top;" class="border pa-5 ht2">
                <div class="ht2">{!! nl2br($risk['pattern'][7]) !!}</div>
            </td>
        </tr>
    </table>
    <div class="footer">powered by Innovation Gate ,Inc.</div>
</body>
</html>
