<?php namespace App\Models;

use Laracasts\Presenter\PresentableTrait;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Events\ExpenseWasCreated;
use App\Events\ExpenseWasUpdated;
use App\Events\ExpenseWasDeleted;

/**
 * Class Expense
 */
class Expense extends EntityModel
{
    // Expenses
    use SoftDeletes;
    use PresentableTrait;

    /**
     * @var array
     */
    protected $dates = ['deleted_at'];
    /**
     * @var string
     */
    protected $presenter = 'App\Ninja\Presenters\ExpensePresenter';

    /**
     * @var array
     */
    protected $fillable = [
        'client_id',
        'vendor_id',
        'expense_currency_id',
        'invoice_currency_id',
        'amount',
        'foreign_amount',
        'exchange_rate',
        'private_notes',
        'public_notes',
        'bank_id',
        'transaction_id',
        'expense_category_id',
        'tax_rate1',
        'tax_name1',
        'tax_rate2',
        'tax_name2',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function expense_category()
    {
        return $this->belongsTo('App\Models\ExpenseCategory')->withTrashed();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function account()
    {
        return $this->belongsTo('App\Models\Account');
    }

    /**
     * @return mixed
     */
    public function user()
    {
        return $this->belongsTo('App\Models\User')->withTrashed();
    }

    /**
     * @return mixed
     */
    public function vendor()
    {
        return $this->belongsTo('App\Models\Vendor')->withTrashed();
    }

    /**
     * @return mixed
     */
    public function client()
    {
        return $this->belongsTo('App\Models\Client')->withTrashed();
    }

    /**
     * @return mixed
     */
    public function invoice()
    {
        return $this->belongsTo('App\Models\Invoice')->withTrashed();
    }

    /**
     * @return mixed
     */
    public function documents()
    {
        return $this->hasMany('App\Models\Document')->orderBy('id');
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->transaction_id ?: '#' . $this->public_id;
    }

    /**
     * @return mixed
     */
    public function getDisplayName()
    {
        return $this->getName();
    }

    /**
     * @return string
     */
    public function getRoute()
    {
        return "/expenses/{$this->public_id}";
    }

    /**
     * @return mixed
     */
    public function getEntityType()
    {
        return ENTITY_EXPENSE;
    }

    /**
     * @return bool
     */
    public function isExchanged()
    {
        return $this->invoice_currency_id != $this->expense_currency_id || $this->exchange_rate != 1;
    }

    /**
     * @return float
     */
    public function convertedAmount()
    {
        return round($this->amount * $this->exchange_rate, 2);
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $array = parent::toArray();

        if(empty($this->visible) || in_array('converted_amount', $this->visible))$array['converted_amount'] = $this->convertedAmount();

        return $array;
    }

    /**
     * @param $query
     * @param null $bankdId
     * @return mixed
     */
    public function scopeBankId($query, $bankdId = null)
    {
        if ($bankdId) {
            $query->whereBankId($bankId);
        }

        return $query;
    }
}

Expense::creating(function ($expense) {
    $expense->setNullValues();
});

Expense::created(function ($expense) {
    event(new ExpenseWasCreated($expense));
});

Expense::updating(function ($expense) {
    $expense->setNullValues();
});

Expense::updated(function ($expense) {
    event(new ExpenseWasUpdated($expense));
});

Expense::deleting(function ($expense) {
    $expense->setNullValues();
});

Expense::deleted(function ($expense) {
    event(new ExpenseWasDeleted($expense));
});
