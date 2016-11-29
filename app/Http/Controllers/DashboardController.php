<?php namespace App\Http\Controllers;

use stdClass;
use Auth;
use DB;
use View;
use Utils;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Payment;
use App\Ninja\Repositories\DashboardRepository;

/**
 * Class DashboardController
 */
class DashboardController extends BaseController
{
    public function __construct(DashboardRepository $dashboardRepo)
    {
        $this->dashboardRepo = $dashboardRepo;
    }

    /**
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {
        $user = Auth::user();
        $viewAll = $user->hasPermission('view_all');
        $userId = $user->id;
        $account = $user->account;
        $accountId = $account->id;

        $dashboardRepo = $this->dashboardRepo;
        $metrics = $dashboardRepo->totals($accountId, $userId, $viewAll);
        $paidToDate = $dashboardRepo->paidToDate($accountId, $userId, $viewAll);
        $averageInvoice = $dashboardRepo->averages($accountId, $userId, $viewAll);
        $balances = $dashboardRepo->balances($accountId, $userId, $viewAll);
        $activities = $dashboardRepo->activities($accountId, $userId, $viewAll);
        $pastDue = $dashboardRepo->pastDue($accountId, $userId, $viewAll);
        $upcoming = $dashboardRepo->upcoming($accountId, $userId, $viewAll);
        $payments = $dashboardRepo->payments($accountId, $userId, $viewAll);
        $expenses = $dashboardRepo->expenses($accountId, $userId, $viewAll);
        $tasks = $dashboardRepo->tasks($accountId, $userId, $viewAll);

        // check if the account has quotes
        $hasQuotes = false;
        foreach ([$upcoming, $pastDue] as $data) {
            foreach ($data as $invoice) {
                if ($invoice->invoice_type_id == INVOICE_TYPE_QUOTE) {
                    $hasQuotes = true;
                }
            }
        }

        // check if the account has multiple curencies
        $currencyIds = $account->currency_id ? [$account->currency_id] : [DEFAULT_CURRENCY];
        $data = Client::scope()
            ->withArchived()
            ->distinct()
            ->get(['currency_id'])
            ->toArray();

        array_map(function ($item) use (&$currencyIds) {
            $currencyId = intval($item['currency_id']);
            if ($currencyId && ! in_array($currencyId, $currencyIds)) {
                $currencyIds[] = $currencyId;
            }
        }, $data);

        $currencies = [];
        foreach ($currencyIds as $currencyId) {
            $currencies[$currencyId] = Utils::getFromCache($currencyId, 'currencies')->code;
        }

        $data = [
            'account' => $user->account,
            'paidToDate' => $paidToDate,
            'balances' => $balances,
            'averageInvoice' => $averageInvoice,
            'invoicesSent' => $metrics ? $metrics->invoices_sent : 0,
            'activeClients' => $metrics ? $metrics->active_clients : 0,
            'activities' => $activities,
            'pastDue' => $pastDue,
            'upcoming' => $upcoming,
            'payments' => $payments,
            'title' => trans('texts.dashboard'),
            'hasQuotes' => $hasQuotes,
            'showBreadcrumbs' => false,
            'currencies' => $currencies,
            'expenses' => $expenses,
            'tasks' => $tasks,
        ];

        return View::make('dashboard', $data);
    }

    public function chartData($groupBy, $startDate, $endDate, $currencyCode, $includeExpenses)
    {
        $includeExpenses = filter_var($includeExpenses, FILTER_VALIDATE_BOOLEAN);
        $data = $this->dashboardRepo->chartData(Auth::user()->account, $groupBy, $startDate, $endDate, $currencyCode, $includeExpenses);

        return json_encode($data);
    }
}
