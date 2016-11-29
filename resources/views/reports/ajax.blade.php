@if (count($displayData))
    @foreach ($displayData as $record)
        <tr>
            @foreach ($record as $field)
                <td>{!! $field !!}</td>
            @endforeach

        </tr>
    @endforeach
    <tr>
        <td colspan="{{count($columns) - 1}}" align="right" style="background-color: #fcfcfc; border:0px">Total Outstanding</td>
        <td>{!! $totalOutstanding !!}</td>
    </tr>
    <tr>
        <td colspan="{{count($columns) - 1}}" align="right" style="background-color: #fcfcfc; border:0px">Total Due</td>
        <td>{!! $due !!}</td>
    </tr>
@else
    <tr>
        <td colspan="10" style="text-align: center">{{ trans('texts.empty_table') }}</td>
    </tr>
@endif