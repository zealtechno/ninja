<style type="text/css">
     table.invoice-table {
     color: #333;
 }
 table.table {
     clear: both;
     margin-bottom: 6px !important;
     max-width: none !important;
 }
 .table {
     width: 100%;
     max-width: 100%;
     margin-bottom: 20px;
 }
 table {
     background-color: transparent;
 }
 table {
     border-spacing: 0;
     border-collapse: collapse;
 }
 .invoice-table tbody {
     border-style: none !important;
 }
 .table > caption + thead > tr:first-child > th, .table > colgroup + thead > tr:first-child > th, .table > thead:first-child > tr:first-child > th, .table > caption + thead > tr:first-child > td, .table > colgroup + thead > tr:first-child > td, .table > thead:first-child > tr:first-child > td {
     border-top: 0;
 }
 table.dataTable thead > tr > th, table.invoice-table thead > tr > th {
     background-color: #777 !important;
     color: #fff;
 }
 table.table thead > tr > th {
     border-bottom-width: 0px;
 }
 table.invoice-table>thead>tr>th, table.invoice-table>tbody>tr>th, table.invoice-table>tfoot>tr>th, table.invoice-table>thead>tr>td, table.invoice-table>tbody>tr>td, table.invoice-table>tfoot>tr>td {
     border-bottom: 1px solid #dfe0e1;
 }
 
 table.dataTable thead th, table.dataTable thead td, table.invoice-table thead th, table.invoice-table thead td {
     padding: 12px 10px;
 }
 .table>thead>tr>th, .table>tbody>tr>th, .table>tfoot>tr>th, .table>thead>tr>td, .table>tbody>tr>td, .table>tfoot>tr>td {
     vertical-align: middle;
     border-top: none;
 }
 .table > thead > tr > th {
     vertical-align: bottom;
     border-bottom: 2px solid #ddd;
 }
 .table > thead > tr > th, .table > tbody > tr > th, .table > tfoot > tr > th, .table > thead > tr > td, .table > tbody > tr > td, .table > tfoot > tr > td {
     padding: 8px;
     line-height: 1.42857143;
     vertical-align: top;
     border-top: 1px solid #ddd;
 }
 thead th {
     border-left: 1px solid #999;
 }
 th {
     text-align: left;
 }
 </style>
 <table class="table table-striped invoice-table save_pdf">
     <thead>
         <tr>
             @foreach ($columns as $column)
                 <th>{{ trans("texts.{$column}") }}</th>
             @endforeach
         </tr>
     </thead>
     <tbody id="table_content">
         @if (count($displayData))
             @foreach ($displayData as $record)
                 <tr>
                     @foreach ($record as $field)
                         <td>{!! $field !!}</td>
                     @endforeach
 
                 </tr>
             @endforeach
             <tr>
                 <td colspan="{{count($columns) - 1}}" align="right" style="border:0px">Total Outstanding</td>
                 <td>{!! $totalOutstanding !!}</td>
             </tr>
             <tr>
                 <td colspan="{{count($columns) - 1}}" align="right" style="border:0px">Total Due</td>
                 <td>{!! $due !!}</td>
             </tr>
         @else
             <tr>
                 <td colspan="10" style="text-align: center">{{ trans('texts.empty_table') }}</td>
             </tr>
         @endif
     </tbody>
 </table> 