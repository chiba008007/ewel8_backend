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
    .txtleft{
        text-align:left;
    }
    .txtcenter{
        text-align:center;
    }
    .texttop{
        vertical-align: top;
    }
    .box{
        border:1px solid #000;
        padding:5px;
        height:100px;
    }
    .mt-3{
        margin-top:3px;
    }
    .graphBox{
        margin-top:5px;
        margin-left:0;
        width:100%;
        height:440px;
        border:1px solid #000;
    }
    .absolute-image{
        position:absolute;
        top:383;
        left:200;
    }
    .absolute{
        position:absolute;
        width:140px;
        font-size:11px;
    }
    .text1{
        top:385;
        left:325;
        text-align: center;
    }
    .text2{
        top:430;
        left:500;
        text-align: left;
    }
    .text3{
        top:500;
        left:570;
        text-align: left;
    }
    .text4{
        top:580;
        left:580;
        text-align: left;
    }
    .text5{
        top:670;
        left:560;
        text-align: left;
    }
    .text6{
        top:720;
        left:500;
        text-align: left;
    }
    .text7{
        top:750;
        left:325;
        text-align: center;
    }
    .text8{
        top:720;
        left:150;
        text-align: right;
    }
    .text9{
        top:670;
        left:90;
        text-align: right;
    }
    .text10{
        top:580;
        left:70;
        text-align: right;

    }
    .text11{
        top:500;
        left:90;
        text-align: right;
    }
    .text12{
        top:430;
        left:130;
        text-align: right;
    }
    .box2{
        border:1px solid #000;
        padding:3px;
    }
    .leftTop{
        top:360;
        left:50px;
        padding:5px;
        border:1px double #000;
        text-align: center;
    }
    .rightTop{
        top:360;
        left:596px;
        padding:5px;
        border:1px double #000;
        text-align: center;
    }
    .leftBottom{
        top:740;
        left:50px;
        padding:5px;
        border:1px double #000;
        text-align: center;
    }
    .rightBottom{
        top:740;
        left:596px;
        padding:5px;
        border:1px double #000;
        text-align: center;
    }
    .wmin{
        width:140px;
        height:135px;
    }
    .footer{
        position: absolute;
        bottom:0;
        right:0;
    }
</style>
</head>
<body>
    @if ($row > 0 )
        <div style="page-break-before: always"></div>
    @endif
    @include('PDF.HEADER',[
        'title'=>'個人結果シート(自己理解版)','pdfImagePath'=>$pdfImagePath ])
    <div class="mt-3 min">1.行動価値検査で測定していること</div>
    <div class="box mt-3 min">
        行動価値検査は、日々行動する中で「あなたがどのような行動を重視しているのか」について測定しており、能力を測定する検査ではありません。<br />
        この検査は12の特性から構成されており、12の特性は、「自己認知力：自己を適切に認識する力」「自己安定力：自分をコントロールする力」「対人認知力：他者の立場や感情を適切に認識する力」「対人影響力：他者を巻き込み、組織で目標を達成する力」の4つの領域に分かれています。<br />
        12の特性はスコア（偏差値）で表わされています。スコアが高い場合には、日常の行動において、その特性を重視して行動していることを表しています。各特性のスコアは下記の結果をご覧ください。
    </div>
    <div class="mt-3 min">2.行動価値 12特性のスコアとチャート</div>
    <table class="table min" >
        <tr>
            <th class="td green">自己認知力</th>
            <th class="td green short">スコア</th>
            <th class="td green">自己安定力</th>
            <th class="td green short">スコア</th>
            <th class="td green">対人認知力</th>
            <th class="td green short">スコア</th>
            <th class="td green">対人影響力</th>
            <th class="td green short">スコア</th>
        </tr>
        <tr>
            <td class="td txtleft">{{ $value->element1 }}</td>
            <td class="td">{{ $result->dev1n }}</td>
            <td class="td txtleft">{{ $value->element4 }}</td>
            <td class="td">{{ $result->dev4n }}</td>
            <td class="td txtleft">{{ $value->element7 }}</td>
            <td class="td">{{ $result->dev7n }}</td>
            <td class="td txtleft">{{ $value->element10 }}</td>
            <td class="td">{{ $result->dev10n }}</td>
        </tr>
        <tr>
            <td class="td txtleft">{{ $value->element2 }}</td>
            <td class="td">{{ $result->dev2n }}</td>
            <td class="td txtleft">{{ $value->element5 }}</td>
            <td class="td">{{ $result->dev5n }}</td>
            <td class="td txtleft">{{ $value->element8 }}</td>
            <td class="td">{{ $result->dev8n }}</td>
            <td class="td txtleft">{{ $value->element11 }}</td>
            <td class="td">{{ $result->dev11n }}</td>
        </tr>
        <tr>
            <td class="td txtleft">{{ $value->element3 }}</td>
            <td class="td">{{ $result->dev3n }}</td>
            <td class="td txtleft">{{ $value->element6 }}</td>
            <td class="td">{{ $result->dev6n }}</td>
            <td class="td txtleft">{{ $value->element9 }}</td>
            <td class="td">{{ $result->dev9n }}</td>
            <td class="td txtleft">{{ $value->element12 }}</td>
            <td class="td">{{ $result->dev12n }}</td>
        </tr>
    </table>
    <div class="absolute-image">
        <img src="{{ public_path('images/PDF/en01.gif') }}" width=400  >
    </div>
    <div class="absolute leftTop"><div class="box2">対人影響力</div></div>
    <div class="absolute rightTop"><div class="box2">自己認知力</div></div>
    <div class="absolute leftBottom"><div class="box2">対人認知力 </div></div>
    <div class="absolute rightBottom"><div class="box2">自己安定力</div></div>
    <div class="absolute text1">{!! nl2br($element1) !!}</div>
    <div class="absolute text2">{!! nl2br($element2) !!}</div>
    <div class="absolute text3">{!! nl2br($element3) !!}</div>
    <div class="absolute text4">{!! nl2br($element4) !!}</div>
    <div class="absolute text5">{!! nl2br($element5) !!}</div>
    <div class="absolute text6">{!! nl2br($element6) !!}</div>
    <div class="absolute text7">{!! nl2br($element7) !!}</div>
    <div class="absolute text8">{!! nl2br($element8) !!}</div>
    <div class="absolute text9">{!! nl2br($element9) !!}</div>
    <div class="absolute text10">{!! nl2br($element10) !!}</div>
    <div class="absolute text11">{!! nl2br($element11) !!}</div>
    <div class="absolute text12">{!! nl2br($element12) !!}</div>

    <div class="graphBox">

    </div>
    <div class="min mt-3">3.{{ $exam->name }} さんの強み</div>
    <table class="table min" >
        <tr>
            <td class="td green txtcenter" >重視している要素 </td>
            <td class="td green txtcenter" >重視している要素が発揮された場合の特徴</td>
        </tr>
        <tr>
            <td class="td txtleft wmin ">{{ $strong[0]['title'] }}</td>
            <td class="td txtleft texttop">{{ $strong[0]['note'] }}</td>
        </tr>
        <tr>
            <td class="td txtleft wmin">{{ $strong[1]['title'] }}</td>
            <td class="td txtleft texttop">{{ $strong[1]['note'] }}</td>
        </tr>
    </table>
    @include("PDF.FOOTER")
</body>
</html>
