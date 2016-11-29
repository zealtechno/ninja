<?php namespace App\Ninja\Repositories;

use DB;

class AccountGatewayRepository extends BaseRepository
{
    public function getClassName()
    {
        return 'App\Models\AccountGateway';
    }

    public function find($accountId)
    {
        $query = DB::table('account_gateways')
                    ->join('gateways', 'gateways.id', '=', 'account_gateways.gateway_id')
                    ->where('account_gateways.account_id', '=', $accountId);

        if (!\Session::get('show_trash:gateway')) {
            $query->where('account_gateways.deleted_at', '=', null);
        }

        return $query->select('account_gateways.id', 'account_gateways.public_id', 'gateways.name', 'account_gateways.deleted_at', 'account_gateways.gateway_id');
    }
}
