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
    <div class="absolute" style="top:194;left:186;">aaa</div>
    <div class="absolute min" style="top:198;left:551;">53.1</div>
    <div class="absolute min" style="top:198;left:602;">要注意</div>
    <div class="absolute" style="top:240;left:186;">bbb</div>
    <div class="absolute min" style="top:244;left:551;">53.1</div>
    <div class="absolute min" style="top:244;left:602;">要注意</div>
    <div class="absolute" style="top:286;left:186;">ccc</div>
    <div class="absolute min" style="top:290;left:551;">53.1</div>
    <div class="absolute min" style="top:290;left:602;">要注意</div>
    <div class="absolute" style="top:346;left:186;">ddd</div>
    <div class="absolute min" style="top:350;left:551;">53.1</div>
    <div class="absolute min" style="top:350;left:602;">要注意</div>
    <div class="absolute" style="top:390;left:186;">eee</div>
    <div class="absolute min" style="top:394;left:551;">53.1</div>
    <div class="absolute min" style="top:394;left:602;">要注意</div>
    <div class="absolute" style="top:437;left:186;">fff</div>
    <div class="absolute min" style="top:441;left:551;">53.1</div>
    <div class="absolute min" style="top:441;left:602;">要注意</div>
    <div class="absolute" style="top:350;left:664;">ggg</div>
    <div class="absolute min2" style="top:170;left:680;">(2.5/10)</div>

    <div class="mt-3 min">2.パワハラリスクの全体傾向</div>
    <div class="mt-3 pa-5 box min ht">
        スコアのバランスからハラスメントを起こすリスクは現時点で高くないと思われます。しかしながら、部下を持つ管理職であれば、ハラスメントを起こしてしまう可能性に一定の注意が必要です。今後、仕事内容、職場環境、人間関係等が変化することがあれば、あなた自身が重きをおく行動も変化するでしょう。その際、これまで注意して行動できていたことに気が回らなくなってしまい、配慮のない傲慢な言動や無責任な態度をとってしまうことがないように注意しましょう。
    </div>
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
                <div class="ht2">
                    自己肯定リスクについて、ハラスメントを起こす方と同様の傾向が見受けられ、十分な注意が必要です。過去の成功体験や、できるという自信は仕事を進める上で重要な推進力になります。但し、自信過剰になると周囲に対する配慮や注意が散漫になり、失言や傲慢な言動をとってしまう可能性があります。
                </div>
            </td>
            <td>&nbsp;</td>
            <td style="width:49%;vertical-align:top;" class="border pa-5 ht2">
                <div class="ht2">
                    自己肯定リスクについて、ハラスメントを起こす方と同様の傾向が見受けられ、十分な注意が必要です。過去の成功体験や、できるという自信は仕事を進める上で重要な推進力になります。但し、自信過剰になると周囲に対する配慮や注意が散漫になり、失言や傲慢な言動をとってしまう可能性があります。
                </div>
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
                ①自己肯定リスク
            </td>
            <td>&nbsp;</td>
            <td style="width:49%; text-align:center;" class="border pa-3" >
                <div >②状況察知リスク</div>
            </td>
        </tr>
    </table>
    <table class="min w-100 table2 " style="margin-top:4px;">
        <tr>
            <td style="width:49%;vertical-align:top;" class="border pa-5 ht2">
                <div class="ht2">
                    ■「なぜこんなことができないの？」と詰める。<br />
                    ■「こんなの簡単だろう？」と仕事を振る。<br />
                    ■「こんなこと、私にいちいち言わせるな」と怒る。<br />
                    ■「私は○○さんとは違うんだ」と誇る。<br />
                    ■「言う通りにすればいい」と意見を受け付けない。
                </div>
            </td>
            <td>&nbsp;</td>
            <td style="width:49%;vertical-align:top;" class="border pa-5 ht2">
                <div class="ht2">
                    ■「とにかくやってみろ」と仕事を振る。<br />
                    ■ 言いたいことをつい主張してしまう。<br />
                    ■ 自分の言動で場が盛り上げらず、静かになってしまう。<br />
                    ■「あいつが考えていることが分からない」とよく思う。<br />
                    ■「どんな状況でも対応できる」と自信を持っている。
                </div>
            </td>
        </tr>
    </table>
    <div class="footer">powered by Innovation Gate ,Inc.</div>
</body>
</html>
