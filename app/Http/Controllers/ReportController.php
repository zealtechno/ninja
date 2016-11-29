<?php namespace App\Http\Controllers;

use Auth;
use Config;
use Input;
use Utils;
use DB;
use Session;
use View;
use App\Models\Account;
use App\Models\Client;
use App\Models\Payment;
use App\Models\Expense;
use Carbon\Carbon;
use App\Ninja\Mailers\ContactMailer;
use Mail;

/**
 * Class ReportController
 */
class ReportController extends BaseController
{
    /**
     * @return \Illuminate\Contracts\View\View
     */
    public function d3()
    {
        $message = '';
        $fileName = storage_path().'/dataviz_sample.txt';

        if (Auth::user()->account->hasFeature(FEATURE_REPORTS)) {
            $account = Account::where('id', '=', Auth::user()->account->id)
                            ->with(['clients.invoices.invoice_items', 'clients.contacts'])
                            ->first();
            $account = $account->hideFieldsForViz();
            $clients = $account->clients->toJson();
        } elseif (file_exists($fileName)) {
            $clients = file_get_contents($fileName);
            $message = trans('texts.sample_data');
        } else {
            $clients = '[]';
        }

        $data = [
            'clients' => $clients,
            'message' => $message,
        ];

        return View::make('reports.d3', $data);
    }

    /**
     * @return \Illuminate\Contracts\View\View
     */
    public function showReports()
    {
        $action = Input::get('action');

        if (Input::all()) {
            $reportType = Input::get('report_type');
            $dateField = Input::get('date_field');
            $startDate = Utils::toSqlDate(Input::get('start_date'), false);
            $endDate = Utils::toSqlDate(Input::get('end_date'), false);
        } else {
            $reportType = ENTITY_INVOICE;
            $dateField = FILTER_INVOICE_DATE;
            $startDate = Utils::today(false)->modify('-3 month');
            $endDate = Utils::today(false);
        }

        $reportTypes = [
            ENTITY_CLIENT => trans('texts.client'),
            ENTITY_INVOICE => trans('texts.invoice'),
            ENTITY_PRODUCT => trans('texts.product'),
            ENTITY_PAYMENT => trans('texts.payment'),
            ENTITY_EXPENSE => trans('texts.expense'),
            ENTITY_TAX_RATE => trans('texts.tax'),
        ];

        $params = [
            'startDate' => $startDate->format(Session::get(SESSION_DATE_FORMAT)),
            'endDate' => $endDate->format(Session::get(SESSION_DATE_FORMAT)),
            'reportTypes' => $reportTypes,
            'reportType' => $reportType,
            'title' => trans('texts.charts_and_reports'),
        ];

        if (Auth::user()->account->hasFeature(FEATURE_REPORTS)) {
            $isExport = $action == 'export';
            $params = array_merge($params, self::generateReport($reportType, $startDate, $endDate, $dateField, $isExport));

            if ($isExport) {
                self::export($reportType, $params['displayData'], $params['columns'], $params['reportTotals']);
            }
        } else {
            $params['columns'] = [];
            $params['displayData'] = [];
            $params['reportTotals'] = [];
        }

        // var_dump($params);
        // exit;

        return View::make('reports.chart_builder', $params);
    }

    /**
     * @param $reportType
     * @param $startDate
     * @param $endDate
     * @param $dateField
     * @param $isExport
     * @return array
     */
    private function generateReport($reportType, $startDate, $endDate, $dateField, $isExport)
    {
        if ($reportType == ENTITY_CLIENT) {
            return $this->generateClientReport($startDate, $endDate, $isExport);
        } elseif ($reportType == ENTITY_INVOICE) {
            return $this->generateInvoiceReport($startDate, $endDate, $isExport);
        } elseif ($reportType == ENTITY_PRODUCT) {
            return $this->generateProductReport($startDate, $endDate, $isExport);
        } elseif ($reportType == ENTITY_PAYMENT) {
            return $this->generatePaymentReport($startDate, $endDate, $isExport);
        } elseif ($reportType == ENTITY_TAX_RATE) {
            return $this->generateTaxRateReport($startDate, $endDate, $dateField, $isExport);
        } elseif ($reportType == ENTITY_EXPENSE) {
            return $this->generateExpenseReport($startDate, $endDate, $isExport);
        }
    }
    /**
     * @param $startDate
     * @param $endDate
     * @param $dateField
     * @param $isExport
     * @return array
     */
    private function generateTaxRateReport($startDate, $endDate, $dateField, $isExport)
    {
        $columns = ['tax_name', 'tax_rate', 'amount', 'paid'];
        $account = Auth::user()->account;
        $displayData = [];
        $reportTotals = [];
        $clients = Client::scope()
                        ->withArchived()
                        ->with('contacts')
                        ->with(['invoices' => function($query) use ($startDate, $endDate, $dateField) {
                            $query->with('invoice_items')->withArchived();
                            if ($dateField == FILTER_INVOICE_DATE) {
                                $query->where('invoice_date', '>=', $startDate)
                                      ->where('invoice_date', '<=', $endDate)
                                      ->with('payments');
                            } else {
                                $query->whereHas('payments', function($query) use ($startDate, $endDate) {
                                            $query->where('payment_date', '>=', $startDate)
                                                  ->where('payment_date', '<=', $endDate)
                                                  ->withArchived();
                                        })
                                        ->with(['payments' => function($query) use ($startDate, $endDate) {
                                            $query->where('payment_date', '>=', $startDate)
                                                  ->where('payment_date', '<=', $endDate)
                                                  ->withArchived();
                                        }]);
                            }
                        }]);
        foreach ($clients->get() as $client) {
            $currencyId = $client->currency_id ?: Auth::user()->account->getCurrencyId();
            $amount = 0;
            $paid = 0;
            $taxTotals = [];
            foreach ($client->invoices as $invoice) {
                foreach ($invoice->getTaxes(true) as $key => $tax) {
                    if ( ! isset($taxTotals[$currencyId])) {
                        $taxTotals[$currencyId] = [];
                    }
                    if (isset($taxTotals[$currencyId][$key])) {
                        $taxTotals[$currencyId][$key]['amount'] += $tax['amount'];
                        $taxTotals[$currencyId][$key]['paid'] += $tax['paid'];
                    } else {
                        $taxTotals[$currencyId][$key] = $tax;
                    }
                }
                $amount += $invoice->amount;
                $paid += $invoice->getAmountPaid();
            }
            foreach ($taxTotals as $currencyId => $taxes) {
                foreach ($taxes as $tax) {
                    $displayData[] = [
                        $tax['name'],
                        $tax['rate'] . '%',
                        $account->formatMoney($tax['amount'], $client),
                        $account->formatMoney($tax['paid'], $client)
                    ];
                }
                $reportTotals = $this->addToTotals($reportTotals, $client->currency_id, 'amount', $tax['amount']);
                $reportTotals = $this->addToTotals($reportTotals, $client->currency_id, 'paid', $tax['paid']);
            }
        }
        return [
            'columns' => $columns,
            'displayData' => $displayData,
            'reportTotals' => $reportTotals,
        ];
    }
    /**
     * @param $startDate
     * @param $endDate
     * @param $isExport
     * @return array
     */
    private function generatePaymentReport($startDate, $endDate, $isExport)
    {
        $columns = ['client', 'invoice_number', 'invoice_date', 'amount', 'payment_date', 'paid', 'method'];
        $account = Auth::user()->account;
        $displayData = [];
        $reportTotals = [];
        $payments = Payment::scope()
                        ->withArchived()
                        ->excludeFailed()
                        ->whereHas('client', function($query) {
                            $query->where('is_deleted', '=', false);
                        })
                        ->whereHas('invoice', function($query) {
                            $query->where('is_deleted', '=', false);
                        })
                        ->with('client.contacts', 'invoice', 'payment_type', 'account_gateway.gateway')
                        ->where('payment_date', '>=', $startDate)
                        ->where('payment_date', '<=', $endDate);
        foreach ($payments->get() as $payment) {
            $invoice = $payment->invoice;
            $client = $payment->client;
            $displayData[] = [
                $isExport ? $client->getDisplayName() : $client->present()->link,
                $isExport ? $invoice->invoice_number : $invoice->present()->link,
                $invoice->present()->invoice_date,
                $account->formatMoney($invoice->amount, $client),
                $payment->present()->payment_date,
                $account->formatMoney($payment->getCompletedAmount(), $client),
                $payment->present()->method,
            ];
            $reportTotals = $this->addToTotals($reportTotals, $client->currency_id, 'amount', $invoice->amount);
            $reportTotals = $this->addToTotals($reportTotals, $client->currency_id, 'paid', $payment->getCompletedAmount());
        }
        return [
            'columns' => $columns,
            'displayData' => $displayData,
            'reportTotals' => $reportTotals,
        ];
    }
    /**
     * @param $startDate
     * @param $endDate
     * @param $isExport
     * @return array
     */
     private function generateInvoiceReport($startDate, $endDate, $isExport, $clientId=null)
    {
        // $columns = ['client', 'invoice_number', 'invoice_date', 'amount', 'payment_date', 'paid', 'method'];
        $columns = ['invoice_number', 'amount', 'due_date', 'amount_paid', 'payment_date','outstanding_amount'];
        $account = Auth::user()->account;
        $displayData = [];
        $reportTotals = [];
        if ($clientId != null)
        {
            $clients = Client::scope()
                            ->withTrashed()
                            ->with('contacts')
                            ->where('is_deleted', '=', false)
                            ->where('id', $clientId)
                            ->with(['invoices' => function($query) use ($startDate, $endDate) {
                                $query->invoices()
                                      ->withArchived()
                                      ->where('invoice_date', '>=', $startDate)
                                      ->where('invoice_date', '<=', $endDate)
                                      ->with(['payments' => function($query) {
                                            $query->withArchived()
                                                  ->excludeFailed()
                                                  ->with('payment_type', 'account_gateway.gateway');
                                      }, 'invoice_items'])
                                      ->withTrashed();
                            }]);
        }
        else
        {
            $clients = Client::scope()
                            ->withTrashed()
                            ->with('contacts')
                            ->where('is_deleted', '=', false)
                            ->with(['invoices' => function($query) use ($startDate, $endDate) {
                                $query->invoices()
                                      ->withArchived()
                                      ->where('invoice_date', '>=', $startDate)
                                      ->where('invoice_date', '<=', $endDate)
                                      ->with(['payments' => function($query) {
                                            $query->withArchived()
                                                  ->excludeFailed()
                                                  ->with('payment_type', 'account_gateway.gateway');
                                      }, 'invoice_items'])
                                      ->withTrashed();
                            }]);
        }
        $totalOutstanding = 0;
        $due = 0;
        foreach ($clients->get() as $client) {
            foreach ($client->invoices as $invoice) {
                $payments = count($invoice->payments) ? $invoice->payments : [false];
                foreach ($payments as $payment) {
                    if(!$payment && $invoice->due_date > Carbon::now()) {
                        $outstanding = $account->formatMoney($invoice->balance, $client);
                        } elseif(!$payment && $invoice->due_date <= Carbon::now()) {
                            $outstanding = $account->formatMoney($invoice->balance, $client);
                        } else {
                        if($invoice->due_date > Carbon::now()) {
                            $outstanding = $invoice->amount - $payment->getCompletedAmount();
                            $outstanding = $account->formatMoney($outstanding, $client);
                        } else {
                           $outstanding = $account->formatMoney($invoice->balance, $client);
                        }
                    }
                    $displayData[] = [
                        //$isExport ? $client->getDisplayName() : $client->present()->link,
                        $isExport ? $invoice->invoice_number : $invoice->present()->link,
                        $account->formatMoney($invoice->amount, $client),
                        $invoice->present()->due_date,
                        $payment ? $account->formatMoney($payment->getCompletedAmount(), $client) : '',
                        $payment ? $payment->present()->payment_date : '',
                        $outstanding,
                    ];
                }
                if($invoice->due_date < Carbon::now()) {
                    $totalOutstanding += $invoice->balance;
                    $due += $invoice->balance;
                } else {
                    $totalOutstanding += $invoice->balance;
                }
            }
        }
        $due = $account->formatMoney($due, $client);
        $totalOutstanding = $account->formatMoney($totalOutstanding, $client);
        return [
            'columns' => $columns,
            'displayData' => $displayData,
            'totalOutstanding' => $totalOutstanding,
            'due' => $due
        ];
    }
    /**
     * @param $startDate
     * @param $endDate
     * @param $isExport
     * @return array
     */
    private function generateProductReport($startDate, $endDate, $isExport)
    {
        $columns = ['client', 'invoice_number', 'invoice_date', 'quantity', 'product'];
        $account = Auth::user()->account;
        $displayData = [];
        $reportTotals = [];
        $clients = Client::scope()
                        ->withTrashed()
                        ->with('contacts')
                        ->where('is_deleted', '=', false)
                        ->with(['invoices' => function($query) use ($startDate, $endDate) {
                            $query->where('invoice_date', '>=', $startDate)
                                  ->where('invoice_date', '<=', $endDate)
                                  ->where('is_deleted', '=', false)
                                  ->where('is_recurring', '=', false)
                                  ->where('invoice_type_id', '=', INVOICE_TYPE_STANDARD)
                                  ->with(['invoice_items'])
                                  ->withTrashed();
                        }]);
        foreach ($clients->get() as $client) {
            foreach ($client->invoices as $invoice) {
                foreach ($invoice->invoice_items as $invoiceItem) {
                    $displayData[] = [
                        $isExport ? $client->getDisplayName() : $client->present()->link,
                        $isExport ? $invoice->invoice_number : $invoice->present()->link,
                        $invoice->present()->invoice_date,
                        $invoiceItem->qty,
                        $invoiceItem->product_key,
                    ];
                    //$reportTotals = $this->addToTotals($reportTotals, $client->currency_id, 'paid', $payment ? $payment->amount : 0);
                }
                //$reportTotals = $this->addToTotals($reportTotals, $client->currency_id, 'amount', $invoice->amount);
                //$reportTotals = $this->addToTotals($reportTotals, $client->currency_id, 'balance', $invoice->balance);
            }
        }
        return [
            'columns' => $columns,
            'displayData' => $displayData,
            'reportTotals' => [],
        ];
    }
    /**
     * @param $startDate
     * @param $endDate
     * @param $isExport
     * @return array
     */
    private function generateClientReport($startDate, $endDate, $isExport)
    {
        $columns = ['client', 'amount', 'paid', 'balance'];
        $account = Auth::user()->account;
        $displayData = [];
        $reportTotals = [];
        $clients = Client::scope()
                        ->withArchived()
                        ->with('contacts')
                        ->with(['invoices' => function($query) use ($startDate, $endDate) {
                            $query->where('invoice_date', '>=', $startDate)
                                  ->where('invoice_date', '<=', $endDate)
                                  ->where('invoice_type_id', '=', INVOICE_TYPE_STANDARD)
                                  ->where('is_recurring', '=', false)
                                  ->withArchived();
                        }]);
        foreach ($clients->get() as $client) {
            $amount = 0;
            $paid = 0;
            foreach ($client->invoices as $invoice) {
                $amount += $invoice->amount;
                $paid += $invoice->getAmountPaid();
            }
            $displayData[] = [
                $isExport ? $client->getDisplayName() : $client->present()->link,
                $account->formatMoney($amount, $client),
                $account->formatMoney($paid, $client),
                $account->formatMoney($amount - $paid, $client)
            ];
            $reportTotals = $this->addToTotals($reportTotals, $client->currency_id, 'amount', $amount);
            $reportTotals = $this->addToTotals($reportTotals, $client->currency_id, 'paid', $paid);
            $reportTotals = $this->addToTotals($reportTotals, $client->currency_id, 'balance', $amount - $paid);
        }
        return [
            'columns' => $columns,
            'displayData' => $displayData,
            'reportTotals' => $reportTotals,
        ];
    }
    /**
     * @param $startDate
     * @param $endDate
     * @param $isExport
     * @return array
     */
    private function generateExpenseReport($startDate, $endDate, $isExport)
    {
        $columns = ['vendor', 'client', 'date', 'expense_amount', 'invoiced_amount'];
        $account = Auth::user()->account;
        $displayData = [];
        $reportTotals = [];
        $expenses = Expense::scope()
                        ->withTrashed()
                        ->with('client.contacts', 'vendor')
                        ->where('expense_date', '>=', $startDate)
                        ->where('expense_date', '<=', $endDate);
        foreach ($expenses->get() as $expense) {
            $amount = $expense->amount;
            $invoiced = $expense->present()->invoiced_amount;
            $displayData[] = [
                $expense->vendor ? ($isExport ? $expense->vendor->name : $expense->vendor->present()->link) : '',
                $expense->client ? ($isExport ? $expense->client->getDisplayName() : $expense->client->present()->link) : '',
                $expense->present()->expense_date,
                Utils::formatMoney($amount, $expense->currency_id),
                Utils::formatMoney($invoiced, $expense->invoice_currency_id),
            ];
            $reportTotals = $this->addToTotals($reportTotals, $expense->expense_currency_id, 'amount', $amount);
            $reportTotals = $this->addToTotals($reportTotals, $expense->invoice_currency_id, 'amount', 0);
            $reportTotals = $this->addToTotals($reportTotals, $expense->invoice_currency_id, 'invoiced', $invoiced);
            $reportTotals = $this->addToTotals($reportTotals, $expense->expense_currency_id, 'invoiced', 0);
        }
        return [
            'columns' => $columns,
            'displayData' => $displayData,
            'reportTotals' => $reportTotals,
        ];
    }
    /**
     * @param $data
     * @param $currencyId
     * @param $field
     * @param $value
     * @return mixed
     */
    private function addToTotals($data, $currencyId, $field, $value) {
        $currencyId = $currencyId ?: Auth::user()->account->getCurrencyId();
        if (!isset($data[$currencyId][$field])) {
            $data[$currencyId][$field] = 0;
        }
        $data[$currencyId][$field] += $value;
        return $data;
    }
    /**
     * @param $reportType
     * @param $data
     * @param $columns
     * @param $totals
     */
    private function export($reportType, $data, $columns, $totals)
    {
        $output = fopen('php://output', 'w') or Utils::fatalError();
        $reportType = trans("texts.{$reportType}");
        $date = date('Y-m-d');
        header('Content-Type:application/csv');
        header("Content-Disposition:attachment;filename={$date}_Ninja_{$reportType}.csv");
        Utils::exportData($output, $data, Utils::trans($columns));
        fwrite($output, trans('texts.totals'));
        foreach ($totals as $currencyId => $fields) {
            foreach ($fields as $key => $value) {
                fwrite($output, ',' . trans("texts.{$key}"));
            }
            fwrite($output, "\n");
            break;
        }
        foreach ($totals as $currencyId => $fields) {
            $csv = Utils::getFromCache($currencyId, 'currencies')->name . ',';
            foreach ($fields as $key => $value) {
                $csv .= '"' . Utils::formatMoney($value, $currencyId).'",';
            }
            fwrite($output, $csv."\n");
        }
        fclose($output);
        exit;
    }
    
    public function getClientSearch()
    {
        $clients = Client::scope()->with('contacts', 'country')->orderBy('name');
        if (!Auth::user()->hasPermission('view_all')) {
            $clients = $clients->where('clients.user_id', '=', Auth::user()->id);
        }
        return response()->json($clients->get());
    }
    public function getReportMailPdf()
    {   
        $reportType = ENTITY_INVOICE;
        $dateField = FILTER_INVOICE_DATE;
        $startDate = Input::get('start_date')!=''?Utils::toSqlDate(Input::get('start_date'), false):Carbon::create(2000, 1, 1, 0, 0, 0);
        $endDate = Input::get('end_date')!=''?Utils::toSqlDate(Input::get('end_date'), false):Carbon::now();
        $clientId = Input::get('client_id')!=''?Input::get('client_id'):null;
        $params = [
            'startDate' => $startDate->format(Session::get(SESSION_DATE_FORMAT)),
            'endDate' => $endDate->format(Session::get(SESSION_DATE_FORMAT))
        ];
        if (Auth::user()->account->hasFeature(FEATURE_REPORTS)) {
            $isExport = true;
            $params = array_merge($params, self::generateReport($reportType, $startDate, $endDate, $dateField, $isExport, $clientId));
        } else {
            $params['columns'] = [];
            $params['displayData'] = [];
            $params['reportTotals'] = [];
        }
        if (Input::get('action') == 'mail')
        {
            $fromEmail = CONTACT_EMAIL;
            $email = Auth::user()->email;
            Mail::send('emails.report_email', $params, function($message) use ($email, $fromEmail)
            {
                $message->from($fromEmail);
                $message->to($email);
            });
            return view('emails.report_email', $params);
        }
        else
        {
            $pdf = \PDF::loadView('emails.report_email', $params);
            return $pdf->download('report_email.pdf');
        }
    }
}
