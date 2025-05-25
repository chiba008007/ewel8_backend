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
        font-size:11px;
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
        font-size:10px;
    }
    .min2{
        font-size:8px;
    }
    .middle{
        font-size:12px;
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
        left:693;
    }
    .tate00{ top:465; height:1px;}
    .tate05{ top:485; height:15px;}
    .tate10{ top:470; height:30px;}
    .tate15{ top:452; height:48px;}
    .tate20{ top:437; height:62px;}
    .tate25{ top:420; height:80px;}
    .tate30{ top:405; height:95px;}
    .tate35{ top:389; height:110px;}
    .tate40{ top:375; height:124px;}
    .tate45{ top:360; height:140px;}
    .tate50{ top:342; height:158px;}
    .tate55{ top:326; height:173px;}
    .tate60{ top:311; height:188px;}
    .tate65{ top:295; height:205px;}
    .tate70{ top:280; height:220px;}
    .tate75{ top:262; height:237px;}
    .tate80{ top:247; height:253px;}
    .tate85{ top:232; height:268px;}
    .tate90{ top:217; height:282px;}
    .tate95{ top:198; height:301px;}
    .tate100{ top:180; height:320px;}
    .center{ text-align: center; }
</style>
</head>
<body>
    @if ($row > 0)
        <div style="page-break-before: always"></div>
    @endif
    @include('PDF.HEADER',[ 'title'=>'パワハラ傾向振り返りシート' ])
    <div class="mt-3 middle">1.要注意や危険が３つ以上ある場合は自身の言動をしっかり振り返りましょう</div>
    <div class="mt-3">
        <img src="{{ public_path('images/PDF/pawahara.png') }}" width="100%" />
    </div>
    <div class="absolute bar" style="top:194;left:162;width:{{ $risk[1]['width'] }}px;">&nbsp;</div>
    <div class="absolute middle" style="top:196;left:570;">{{ $risk[1]['point'] }}</div>
    <div class="absolute middle center" style="top:196;left:615;width:60px;">{{ $risk[1]['text'] }}</div>

    <div class="absolute bar" style="top:245;left:162;width:{{ $risk[2]['width'] }}px;">&nbsp;</div>
    <div class="absolute middle" style="top:247;left:570;">{{ $risk[2]['point'] }}</div>
    <div class="absolute middle center" style="top:247;left:615;width:60px;">{{ $risk[2]['text'] }}</div>

    <div class="absolute bar" style="top:298;left:162;width:{{ $risk[3]['width'] }}px;">&nbsp;</div>
    <div class="absolute middle" style="top:300;left:570;">{{ $risk[3]['point'] }}</div>
    <div class="absolute middle center" style="top:300;left:615;width:60px;">{{ $risk[3]['text'] }}</div>

    <div class="absolute bar" style="top:366;left:162;width:{{ $risk[4]['width'] }}px;">&nbsp;</div>
    <div class="absolute middle" style="top:368;left:570;">{{ $risk[4]['point'] }}</div>
    <div class="absolute middle center" style="top:368;left:615;width:60px;">{{ $risk[4]['text'] }}</div>

    <div class="absolute bar" style="top:416;left:162;width:{{ $risk[5]['width'] }}px;">&nbsp;</div>
    <div class="absolute middle" style="top:418;left:570;">{{ $risk[5]['point'] }}</div>
    <div class="absolute middle center" style="top:418;left:615;width:60px;">{{ $risk[5]['text'] }}</div>

    <div class="absolute bar" style="top:467;left:162;width:{{ $risk[6]['width'] }}px;">&nbsp;</div>
    <div class="absolute middle" style="top:469;left:570;">{{ $risk[6]['point'] }}</div>
    <div class="absolute middle center" style="top:469;left:615;width:60px;">{{ $risk[6]['text'] }}</div>

    <div class="absolute tate tate{{ $risk['tate'] }}">&nbsp;</div>
    <div class="absolute min2" style="top:165;left:710;">({{ $risk[7]['point'] }}/10)</div>

    <div class="mt-3 middle">2.パワハラリスクの全体傾向</div>
    <div class="mt-3 pa-5 box middle ht">{{ $risk['pawahararisk'] }}</div>
    <table class="mt-3 middle w-100">
        <tr>
            <td style="width:50%;">
                <div >3.一番留意すべき項目の傾向</div>
            </td>
            <td style="width:49%;margin-left:2%">
                <div >4.一番、留意すべき項目についての道しるべ(助言)</div>
            </td>
        </tr>
    </table>
    <table class="mt-3 middle w-100 table2 ">
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
    <table class="mt-3 middle w-100">
        <tr>
            <td>
                <div >5.こんな言動に身に覚えがありませんか？</div>
            </td>
        </tr>
    </table>

    <table class="mt-3 middle w-100 table2" >
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
    <table class="middle w-100 table2 " style="margin-top:4px;">
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
    @include("PDF.FOOTER")
</body>
</html>
