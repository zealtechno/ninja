<?php namespace App\Http\Requests;

use App\Models\Invoice;

class CreatePaymentRequest extends PaymentRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user()->can('create', ENTITY_PAYMENT);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $input = $this->input();
        $invoice_count = $input['invoice_count'];
        
        $rules = ['client' => 'required',
                  'payment_date' => 'required',];
        for ($i = 0; $i < $invoice_count; $i ++)
        {
            $invoice = Invoice::scope($input['Invoice'.$i])
            ->invoices()
            ->firstOrFail();    

            $rules['Invoice'.$i] = 'required';
            $rules['Amount'.$i] = "required|numeric|between:0.01,{$invoice->balance}";
        }


        // $rules = [
        //     'client' => 'required', // TODO: change to client_id once views are updated
        //     'Invoice0' => 'required', // TODO: change to invoice_id once views are updated
        //     'Amount0' => "required|numeric|between:0.01,{$invoice->balance}",
        //     'payment_date' => 'required',
        // ];

        if ( ! empty($input['payment_type_id']) && $input['payment_type_id'] == PAYMENT_TYPE_CREDIT) {
            $rules['payment_type_id'] = 'has_credit:'.$input['client'].','.$input['Amount0'];
        }
        return $rules;
    }
}
