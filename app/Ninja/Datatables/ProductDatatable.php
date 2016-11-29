<?php namespace App\Ninja\Datatables;

use Utils;
use URL;
use Auth;
use Str;

class ProductDatatable extends EntityDatatable
{
    public $entityType = ENTITY_PRODUCT;

    public function columns()
    {
        return [
            [
                'product_key',
                function ($model) {
                    return link_to('products/'.$model->public_id.'/edit', $model->product_key)->toHtml();
                }
            ],
            [
                'notes',
                function ($model) {
                    return nl2br(Str::limit($model->notes, 100));
                }
            ],
            [
                'cost',
                function ($model) {
                    return Utils::formatMoney($model->cost);
                }
            ],
            [
                'tax_rate',
                function ($model) {
                    return $model->tax_rate ? ($model->tax_name . ' ' . $model->tax_rate . '%') : '';
                },
                Auth::user()->account->invoice_item_taxes
            ]
        ];
    }

    public function actions()
    {
        return [
            [
                uctrans('texts.edit_product'),
                function ($model) {
                    return URL::to("products/{$model->public_id}/edit");
                }
            ]
        ];
    }

}
