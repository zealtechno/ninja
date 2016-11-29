<?php namespace App\Ninja\Presenters;

use Utils;

/**
 * Class ExpensePresenter
 */
class ExpensePresenter extends EntityPresenter
{

    /**
     * @return string
     */
    public function vendor()
    {
        return $this->entity->vendor ? $this->entity->vendor->getDisplayName() : '';
    }

    /**
     * @return \DateTime|string
     */
    public function expense_date()
    {
        return Utils::fromSqlDate($this->entity->expense_date);
    }

    /**
     * @return int
     */
    public function invoiced_amount()
    {
        return $this->entity->invoice_id ? $this->entity->convertedAmount() : 0;
    }
    
}
