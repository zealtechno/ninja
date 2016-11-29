<?php namespace App\Http\Controllers;

use Auth;
use Session;
use Utils;
use View;
use Input;
use Cache;
use Redirect;
use DB;
use URL;
use DropdownButton;
use App\Models\Invoice;
use App\Models\Client;
use App\Models\Account;
use App\Models\Product;
use App\Models\Expense;
use App\Models\TaxRate;
use App\Models\InvoiceDesign;
use App\Models\Activity;
use App\Ninja\Mailers\ContactMailer as Mailer;
use App\Ninja\Repositories\InvoiceRepository;
use App\Ninja\Repositories\ClientRepository;
use App\Ninja\Repositories\DocumentRepository;
use App\Services\InvoiceService;
use App\Services\PaymentService;
use App\Services\RecurringInvoiceService;
use App\Http\Requests\InvoiceRequest;
use App\Http\Requests\CreateInvoiceRequest;
use App\Http\Requests\UpdateInvoiceRequest;

class InvoiceController extends BaseController
{
    protected $mailer;
    protected $invoiceRepo;
    protected $clientRepo;
    protected $documentRepo;
    protected $invoiceService;
    protected $paymentService;
    protected $recurringInvoiceService;
    protected $entityType = ENTITY_INVOICE;

    public function __construct(Mailer $mailer, InvoiceRepository $invoiceRepo, ClientRepository $clientRepo, InvoiceService $invoiceService, DocumentRepository $documentRepo, RecurringInvoiceService $recurringInvoiceService, PaymentService $paymentService)
    {
        // parent::__construct();

        $this->mailer = $mailer;
        $this->invoiceRepo = $invoiceRepo;
        $this->clientRepo = $clientRepo;
        $this->invoiceService = $invoiceService;
        $this->recurringInvoiceService = $recurringInvoiceService;
        $this->paymentService = $paymentService;
    }

    public function index()
    {
        $data = [
            'title' => trans('texts.invoices'),
            'entityType' => ENTITY_INVOICE,
            'sortCol' => '3',
            'columns' => Utils::trans([
                'checkbox',
                'invoice_number',
                'client',
                'invoice_date',
                'invoice_total',
                'balance_due',
                'due_date',
                'status',
                ''
            ]),
        ];

        return response()->view('list', $data);
    }

    public function getDatatable($clientPublicId = null)
    {
        $accountId = Auth::user()->account_id;
        $search = Input::get('sSearch');

        return $this->invoiceService->getDatatable($accountId, $clientPublicId, ENTITY_INVOICE, $search);
    }

    public function getRecurringDatatable($clientPublicId = null)
    {
        $accountId = Auth::user()->account_id;
        $search = Input::get('sSearch');

        return $this->recurringInvoiceService->getDatatable($accountId, $clientPublicId, ENTITY_RECURRING_INVOICE, $search);
    }

    public function edit(InvoiceRequest $request, $publicId, $clone = false)
    {
        $account = Auth::user()->account;
        $invoice = $request->entity()->load('invitations', 'account.country', 'client.contacts', 'client.country', 'invoice_items', 'documents', 'expenses', 'expenses.documents', 'payments');
        //echo "<pre>"; print_r($invoice->email_send_flag); exit;
        $entityType = $invoice->getEntityType();

        $contactIds = DB::table('invitations')
            ->join('contacts', 'contacts.id', '=', 'invitations.contact_id')
            ->where('invitations.invoice_id', '=', $invoice->id)
            ->where('invitations.account_id', '=', Auth::user()->account_id)
            ->where('invitations.deleted_at', '=', null)
            ->select('contacts.public_id')->lists('public_id');

        $clients = Client::scope()->withTrashed()->with('contacts', 'country');

        if ($clone) {
            $invoice->id = $invoice->public_id = null;
            $invoice->invoice_number = $account->getNextInvoiceNumber($invoice);
            $invoice->balance = $invoice->amount;
            $invoice->invoice_status_id = 0;
            $invoice->invoice_date = date_create()->format('Y-m-d');
            $method = 'POST';
            $url = "{$entityType}s";
        } else {
            $method = 'PUT';
            $url = "{$entityType}s/{$invoice->public_id}";
            $clients->whereId($invoice->client_id);
        }

        $invoice->invoice_date = Utils::fromSqlDate($invoice->invoice_date);
        $invoice->recurring_due_date = $invoice->due_date;// Keep in SQL form
        $invoice->due_date = Utils::fromSqlDate($invoice->due_date);
        $invoice->start_date = Utils::fromSqlDate($invoice->start_date);
        $invoice->end_date = Utils::fromSqlDate($invoice->end_date);
        $invoice->last_sent_date = Utils::fromSqlDate($invoice->last_sent_date);
        $invoice->features = [
            'customize_invoice_design' => Auth::user()->hasFeature(FEATURE_CUSTOMIZE_INVOICE_DESIGN),
            'remove_created_by' => Auth::user()->hasFeature(FEATURE_REMOVE_CREATED_BY),
            'invoice_settings' => Auth::user()->hasFeature(FEATURE_INVOICE_SETTINGS),
        ];

        $actions = [
            ['url' => 'javascript:onCloneClick()', 'label' => trans("texts.clone_{$entityType}")],
            ['url' => URL::to("{$entityType}s/{$entityType}_history/{$invoice->public_id}"), 'label' => trans('texts.view_history')],
            DropdownButton::DIVIDER
        ];

        if ($invoice->invoice_status_id < INVOICE_STATUS_SENT && !$invoice->is_recurring) {
            $actions[] = ['url' => 'javascript:onMarkClick()', 'label' => trans('texts.mark_sent')];
        }

        if ($entityType == ENTITY_QUOTE) {
            if ($invoice->quote_invoice_id) {
                $actions[] = ['url' => URL::to("invoices/{$invoice->quote_invoice_id}/edit"), 'label' => trans('texts.view_invoice')];
            } else {
                $actions[] = ['url' => 'javascript:onConvertClick()', 'label' => trans('texts.convert_to_invoice')];
            }
        } elseif ($entityType == ENTITY_INVOICE) {
            if ($invoice->quote_id) {
                $actions[] = ['url' => URL::to("quotes/{$invoice->quote_id}/edit"), 'label' => trans('texts.view_quote')];
            }

            if (!$invoice->is_recurring && $invoice->balance > 0) {
                $actions[] = ['url' => 'javascript:onPaymentClick()', 'label' => trans('texts.enter_payment')];
            }

            foreach ($invoice->payments as $payment) {
                $label = trans('texts.view_payment');
                if (count($invoice->payments) > 1) {
                    $label .= ' - ' . $account->formatMoney($payment->amount, $invoice->client);
                }
                $actions[] = ['url' => $payment->present()->url, 'label' => $label];
            }
        }

        if (count($actions) > 3) {
            $actions[] = DropdownButton::DIVIDER;
        }

        $actions[] = ['url' => 'javascript:onArchiveClick()', 'label' => trans("texts.archive_{$entityType}")];
        $actions[] = ['url' => 'javascript:onDeleteClick()', 'label' => trans("texts.delete_{$entityType}")];

        $lastSent = ($invoice->is_recurring && $invoice->last_sent_date) ? $invoice->recurring_invoices->last() : null;

        if(!Auth::user()->hasPermission('view_all')){
            $clients = $clients->where('clients.user_id', '=', Auth::user()->id);
        }

        $data = [
                'clients' => $clients->get(),
                'entityType' => $entityType,
                'showBreadcrumbs' => $clone,
                'invoice' => $invoice,
                'method' => $method,
                'invitationContactIds' => $contactIds,
                'url' => $url,
                'title' => trans("texts.edit_{$entityType}"),
                'client' => $invoice->client,
                'isRecurring' => $invoice->is_recurring,
                'actions' => $actions,
                'lastSent' => $lastSent];
        $data = array_merge($data, self::getViewModel($invoice));

        if ($invoice->isSent() && $invoice->getAutoBillEnabled() && !$invoice->isPaid()) {
            $data['autoBillChangeWarning'] = $invoice->client->autoBillLater();
        }

        if ($clone) {
            $data['formIsChanged'] = true;
        }

        // Set the invitation data on the client's contacts
        if (!$clone) {
            $clients = $data['clients'];
            foreach ($clients as $client) {
                if ($client->id != $invoice->client->id) {
                    continue;
                }

                foreach ($invoice->invitations as $invitation) {
                    foreach ($client->contacts as $contact) {
                        if ($invitation->contact_id == $contact->id) {
                            $contact->email_error = $invitation->email_error;
                            $contact->invitation_link = $invitation->getLink();
                            $contact->invitation_viewed = $invitation->viewed_date && $invitation->viewed_date != '0000-00-00 00:00:00' ? $invitation->viewed_date : false;
                            $contact->invitation_openend = $invitation->opened_date && $invitation->opened_date != '0000-00-00 00:00:00' ? $invitation->opened_date : false;
                            $contact->invitation_status = $contact->email_error ? false : $invitation->getStatus();
                        }
                    }
                }

                break;
            }
        }

        return View::make('invoices.edit', $data);
    }

    public function create(InvoiceRequest $request, $clientPublicId = 0, $isRecurring = false)
    {
        $account = Auth::user()->account;

        $entityType = $isRecurring ? ENTITY_RECURRING_INVOICE : ENTITY_INVOICE;
        $clientId = null;

        if ($request->client_id) {
            $clientId = Client::getPrivateId($request->client_id);
        }

        $invoice = $account->createInvoice($entityType, $clientId);
        $invoice->public_id = 0;

        $clients = Client::scope()->with('contacts', 'country')->orderBy('name');
        if (!Auth::user()->hasPermission('view_all')) {
            $clients = $clients->where('clients.user_id', '=', Auth::user()->id);
        }

        $data = [
            'clients' => $clients->get(),
            'entityType' => $invoice->getEntityType(),
            'invoice' => $invoice,
            'method' => 'POST',
            'url' => 'invoices',
            'title' => trans('texts.new_invoice'),
        ];
        $data = array_merge($data, self::getViewModel($invoice));

        return View::make('invoices.edit', $data);
    }

    public function createRecurring(InvoiceRequest $request, $clientPublicId = 0)
    {
        return self::create($request, $clientPublicId, true);
    }

    private static function getViewModel($invoice)
    {
        $recurringHelp = '';
        foreach (preg_split("/((\r?\n)|(\r\n?))/", trans('texts.recurring_help')) as $line) {
            $parts = explode('=>', $line);
            if (count($parts) > 1) {
                $line = $parts[0].' => '.Utils::processVariables($parts[0]);
                $recurringHelp .= '<li>'.strip_tags($line).'</li>';
            } else {
                $recurringHelp .= $line;
            }
        }

        $recurringDueDateHelp = '';
        foreach (preg_split("/((\r?\n)|(\r\n?))/", trans('texts.recurring_due_date_help')) as $line) {
            $parts = explode('=>', $line);
            if (count($parts) > 1) {
                $line = $parts[0].' => '.Utils::processVariables($parts[0]);
                $recurringDueDateHelp .= '<li>'.strip_tags($line).'</li>';
            } else {
                $recurringDueDateHelp .= $line;
            }
        }

        // Create due date options
        $recurringDueDates = [
            trans('texts.use_client_terms') => ['value' => '', 'class' => 'monthly weekly'],
        ];

        $ends = ['th','st','nd','rd','th','th','th','th','th','th'];
        for($i = 1; $i < 31; $i++){
            if ($i >= 11 && $i <= 13) $ordinal = $i. 'th';
            else $ordinal = $i . $ends[$i % 10];

            $dayStr = str_pad($i, 2, '0', STR_PAD_LEFT);
            $str = trans('texts.day_of_month', ['ordinal'=>$ordinal]);

            $recurringDueDates[$str] = ['value' => "1998-01-$dayStr", 'data-num' => $i, 'class' => 'monthly'];
        }
        $recurringDueDates[trans('texts.last_day_of_month')] = ['value' => '1998-01-31', 'data-num' => 31, 'class' => 'monthly'];


        $daysOfWeek = [
            trans('texts.sunday'),
            trans('texts.monday'),
            trans('texts.tuesday'),
            trans('texts.wednesday'),
            trans('texts.thursday'),
            trans('texts.friday'),
            trans('texts.saturday'),
        ];
        foreach(['1st','2nd','3rd','4th'] as $i=>$ordinal){
            foreach($daysOfWeek as $j=>$dayOfWeek){
                $str = trans('texts.day_of_week_after', ['ordinal' => $ordinal, 'day' => $dayOfWeek]);

                $day = $i * 7 + $j  + 1;
                $dayStr = str_pad($day, 2, '0', STR_PAD_LEFT);
                $recurringDueDates[$str] = ['value' => "1998-02-$dayStr", 'data-num' => $day, 'class' => 'weekly'];
            }
        }

        // Tax rate $options
        $account = Auth::user()->account;
        $rates = TaxRate::scope()->orderBy('name')->get();
        $options = [];
        $defaultTax = false;

        foreach ($rates as $rate) {
            $options[$rate->rate . ' ' . $rate->name] = $rate->name . ' ' . ($rate->rate+0) . '%';

            // load default invoice tax
            if ($rate->id == $account->default_tax_rate_id) {
                $defaultTax = $rate;
            }
        }

        // Check for any taxes which have been deleted
        if ($invoice->exists) {
            foreach ($invoice->getTaxes() as $key => $rate) {
                if (isset($options[$key])) {
                    continue;
                }
                $options[$key] = $rate['name'] . ' ' . $rate['rate'] . '%';
            }
        }

        return [
            'data' => Input::old('data'),
            'account' => Auth::user()->account->load('country'),
            'products' => Product::scope()->with('default_tax_rate')->orderBy('product_key')->get(),
            'taxRateOptions' => $options,
            'defaultTax' => $defaultTax,
            'currencies' => Cache::get('currencies'),
            'sizes' => Cache::get('sizes'),
            'paymentTerms' => Cache::get('paymentTerms'),
            'invoiceDesigns' => InvoiceDesign::getDesigns(),
            'invoiceFonts' => Cache::get('fonts'),
            'frequencies' => [
                1 => trans('texts.freq_weekly'),
                2 => trans('texts.freq_two_weeks'),
                3 => trans('texts.freq_four_weeks'),
                4 => trans('texts.freq_monthly'),
                5 => trans('texts.freq_three_months'),
                6 => trans('texts.freq_six_months'),
                7 => trans('texts.freq_annually'),
            ],
            'recurringDueDates' => $recurringDueDates,
            'recurringHelp' => $recurringHelp,
            'recurringDueDateHelp' => $recurringDueDateHelp,
            'invoiceLabels' => Auth::user()->account->getInvoiceLabels(),
            'tasks' => Session::get('tasks') ? json_encode(Session::get('tasks')) : null,
            'expenseCurrencyId' => Session::get('expenseCurrencyId') ?: null,
            'expenses' => Session::get('expenses') ? Expense::scope(Session::get('expenses'))->with('documents', 'expense_category')->get() : [],
        ];

    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store(CreateInvoiceRequest $request)
    {
        $data = $request->input();
        $data['documents'] = $request->file('documents');

        $action = Input::get('action');
        $entityType = Input::get('entityType');

        $invoice = $this->invoiceService->save($data);
        $entityType = $invoice->getEntityType();
        $message = trans("texts.created_{$entityType}");

        $input = $request->input();
        $clientPublicId = isset($input['client']['public_id']) ? $input['client']['public_id'] : false;
        if ($clientPublicId == '-1') {
            $message = $message.' '.trans('texts.and_created_client');
        }

        Session::flash('message', $message);

        if ($action == 'email') {
            return $this->emailInvoice($invoice, Input::get('pdfupload'));
        }

        return redirect()->to($invoice->getRoute());
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int      $id
     * @return Response
     */
    public function update(UpdateInvoiceRequest $request)
    {
        $data = $request->input();
        $data['documents'] = $request->file('documents');

        $action = Input::get('action');
        $entityType = Input::get('entityType');

        $invoice = $this->invoiceService->save($data, $request->entity());
        $entityType = $invoice->getEntityType();
        $message = trans("texts.updated_{$entityType}");
        Session::flash('message', $message);

        if ($action == 'clone') {
            return $this->cloneInvoice($request, $invoice->public_id);
        } elseif ($action == 'convert') {
            return $this->convertQuote($request, $invoice->public_id);
        } elseif ($action == 'email') {
            return $this->emailInvoice($invoice, Input::get('pdfupload'));
        }

        return redirect()->to($invoice->getRoute());
    }


    private function emailInvoice($invoice, $pdfUpload)
    {
        $entityType = $invoice->getEntityType();
        $pdfUpload = Utils::decodePDF($pdfUpload);
        if (!Auth::user()->confirmed) {
            $errorMessage = trans(Auth::user()->registered ? 'texts.confirmation_required' : 'texts.registration_required');
            Session::flash('error', $errorMessage);
            return Redirect::to('invoices/'.$invoice->public_id.'/edit');
        }
        if ($invoice->is_recurring) {
            $response = $this->emailRecurringInvoice($invoice);
        } else {
            $response = $this->mailer->sendInvoice($invoice, false, $pdfUpload);
        }
        $response=true;
        if ($response === true) {
            $message = trans("texts.emailed_{$entityType}");
            DB::table('invoices')->where('id', $invoice->public_id)->update(['email_send_flag' => 'yes']);
            Session::flash('message', $message);
        } else {
            Session::flash('error', $response);
        }
        return Redirect::to("{$entityType}s/{$invoice->public_id}/edit");
    }

    private function emailRecurringInvoice(&$invoice)
    {
        if (!$invoice->shouldSendToday()) {
            if ($date = $invoice->getNextSendDate()) {
                $date = $invoice->account->formatDate($date);
                $date .= ' ' . DEFAULT_SEND_RECURRING_HOUR . ':00 am ' . $invoice->account->getTimezone();
                return trans('texts.recurring_too_soon', ['date' => $date]);
            } else {
                return trans('texts.no_longer_running');
            }
        }

        // switch from the recurring invoice to the generated invoice
        $invoice = $this->invoiceRepo->createRecurringInvoice($invoice);

        // in case auto-bill is enabled then a receipt has been sent
        if ($invoice->isPaid()) {
            return true;
        } else {
            return $this->mailer->sendInvoice($invoice);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int      $id
     * @return Response
     */
    public function show($publicId)
    {
        Session::reflash();

        return Redirect::to("invoices/$publicId/edit");
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int      $id
     * @return Response
     */
    public function bulk($entityType = ENTITY_INVOICE)
    {
        $action = Input::get('bulk_action') ?: Input::get('action');;
        $ids = Input::get('bulk_public_id') ?: (Input::get('public_id') ?: Input::get('ids'));
        $count = $this->invoiceService->bulk($ids, $action);

        if ($count > 0) {
            $key = $action == 'markSent' ? "updated_{$entityType}" : "{$action}d_{$entityType}";
            $message = Utils::pluralize($key, $count);
            Session::flash('message', $message);
        }

        if ($action == 'restore' && $count == 1) {
            return Redirect::to("{$entityType}s/".Utils::getFirst($ids));
        } else {
            return Redirect::to("{$entityType}s");
        }
    }

    public function convertQuote(InvoiceRequest $request)
    {
        $clone = $this->invoiceService->convertQuote($request->entity());

        Session::flash('message', trans('texts.converted_to_invoice'));

        return Redirect::to('invoices/' . $clone->public_id);
    }

    public function cloneInvoice(InvoiceRequest $request, $publicId)
    {
        return self::edit($request, $publicId, true);
    }

    public function invoiceHistory(InvoiceRequest $request)
    {
        $invoice = $request->entity();
        $invoice->load('user', 'invoice_items', 'documents', 'expenses', 'expenses.documents', 'account.country', 'client.contacts', 'client.country');
        $invoice->invoice_date = Utils::fromSqlDate($invoice->invoice_date);
        $invoice->due_date = Utils::fromSqlDate($invoice->due_date);
        $invoice->features = [
            'customize_invoice_design' => Auth::user()->hasFeature(FEATURE_CUSTOMIZE_INVOICE_DESIGN),
            'remove_created_by' => Auth::user()->hasFeature(FEATURE_REMOVE_CREATED_BY),
            'invoice_settings' => Auth::user()->hasFeature(FEATURE_INVOICE_SETTINGS),
        ];
        $invoice->invoice_type_id = intval($invoice->invoice_type_id);

        $activityTypeId = $invoice->isType(INVOICE_TYPE_QUOTE) ? ACTIVITY_TYPE_UPDATE_QUOTE : ACTIVITY_TYPE_UPDATE_INVOICE;
        $activities = Activity::scope(false, $invoice->account_id)
                        ->where('activity_type_id', '=', $activityTypeId)
                        ->where('invoice_id', '=', $invoice->id)
                        ->orderBy('id', 'desc')
                        ->get(['id', 'created_at', 'user_id', 'json_backup']);

        $versionsJson = [];
        $versionsSelect = [];
        $lastId = false;

        foreach ($activities as $activity) {
            if ($backup = json_decode($activity->json_backup)) {
                $backup->invoice_date = Utils::fromSqlDate($backup->invoice_date);
                $backup->due_date = Utils::fromSqlDate($backup->due_date);
                $backup->features = [
                    'customize_invoice_design' => Auth::user()->hasFeature(FEATURE_CUSTOMIZE_INVOICE_DESIGN),
                    'remove_created_by' => Auth::user()->hasFeature(FEATURE_REMOVE_CREATED_BY),
                    'invoice_settings' => Auth::user()->hasFeature(FEATURE_INVOICE_SETTINGS),
                ];
                $backup->invoice_type_id = isset($backup->invoice_type_id) && intval($backup->invoice_type_id) == INVOICE_TYPE_QUOTE;
                $backup->account = $invoice->account->toArray();

                $versionsJson[$activity->id] = $backup;
                $key = Utils::timestampToDateTimeString(strtotime($activity->created_at)) . ' - ' . $activity->user->getDisplayName();
                $versionsSelect[$lastId ? $lastId : 0] = $key;
                $lastId = $activity->id;
            } else {
                Utils::logError('Failed to parse invoice backup');
            }
        }

        if ($lastId) {
            $versionsSelect[$lastId] = Utils::timestampToDateTimeString(strtotime($invoice->created_at)) . ' - ' . $invoice->user->getDisplayName();
        }

        $data = [
            'invoice' => $invoice,
            'versionsJson' => json_encode($versionsJson),
            'versionsSelect' => $versionsSelect,
            'invoiceDesigns' => InvoiceDesign::getDesigns(),
            'invoiceFonts' => Cache::get('fonts'),
        ];

        return View::make('invoices.history', $data);
    }

    public function checkInvoiceNumber($invoiceNumber)
    {
        $count = Invoice::scope()
                    ->whereInvoiceNumber($invoiceNumber)
                    ->withTrashed()
                    ->count();

        return $count ? RESULT_FAILURE : RESULT_SUCCESS;
    }

}
