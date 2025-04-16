<p>
{{ $name }} {{ $person }}様<br >
貴社におかれましてはますますご清祥のこととお喜び申し上げます。<br >
Welcome-k サポートデスクです。
</p>
<p>
    下記、検査において残数が{{ $rest }}件になり、受検できる件数が少なくなってきておりますのでお知らせ致します。
</p>
<p>
    顧客名：{{ $name }}<br>
    検査名：{{ $testname }}<br>
    期間：{{ $startdate }}～{{ $enddate }}<br>
    残数：{{ $rest }}<br>
</p>
<p>
{!! $invgfoot !!}
</p>
