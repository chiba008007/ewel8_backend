<table style="width:100%;">
    <tr>
        <td width=300><img src="{{ public_path('images/PDF/welcome.jpg') }}" /></td>
        <td width=300 style="text-align:right;">
            <h2>{{ $title }}</h2>
        </td>
    </tr>
</table>
<div style="padding:0px" style="font-size:11px;">企業名:{{ $value->name }}企業</div>
<table class="table " style="font-size:11px;" >
    <tr>
        <td class="td green" style="width:60px;">受検日</td>
        <td class="td" style="width:100px;">{{ $result->startdate }}</td>
        <td class="td green" style="width:60px;">受検ID</td>
        <td class="td">{{ $exam->email }}</td>
        <td class="td green" style="width:40px;">氏名</td>
        <td class="td" style="width:240px;">
            {{ $exam->name }}
            ({{ $exam->kana }})
        </td>
        <td class="td green" style="width:40px;">年齢</td>
        <td class="td" style="width:40px;">{{ $age }}</td>
    </tr>
</table>
