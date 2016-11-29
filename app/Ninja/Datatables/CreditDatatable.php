<?php namespace App\Ninja\Datatables;

use Utils;
use URL;
use Auth;

class CreditDatatable extends EntityDatatable
{
    public $entityType = ENTITY_CREDIT;

    public function columns()
    {
        return [
            [
                'client_name',
                function ($model) {
                    if(!Auth::user()->can('viewByOwner', [ENTITY_CLIENT, $model->client_user_id])){
                        return Utils::getClientDisplayName($model);
                    }

                    return $model->client_public_id ? link_to("clients/{$model->client_public_id}", Utils::getClientDisplayName($model))->toHtml() : '';
                },
                ! $this->hideClient
            ],
            [
                'amount',
                function ($model) {
                    return Utils::formatMoney($model->amount, $model->currency_id, $model->country_id) . '<span '.Utils::getEntityRowClass($model).'/>';
                }
            ],
            [
                'balance',
                function ($model) {
                    return Utils::formatMoney($model->balance, $model->currency_id, $model->country_id);
                }
            ],
            [
                'credit_date',
                function ($model) {
                    return Utils::fromSqlDate($model->credit_date);
                }
            ],
            [
                'private_notes',
                function ($model) {
                    return $model->private_notes;
                }
            ]
        ];
    }

    public function actions()
    {
        return [
            [
                trans('texts.apply_credit'),
                function ($model) {
                    return URL::to("payments/create/{$model->client_public_id}") . '?paymentTypeId=1';
                },
                function ($model) {
                    return Auth::user()->can('create', ENTITY_PAYMENT);
                }
            ]
        ];
    }
}
