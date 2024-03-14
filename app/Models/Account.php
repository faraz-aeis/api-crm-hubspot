<?php

/**
 * Account Model
 *
 * @category Model
 * @author   Anoop Singh <asingh@aeis.com>
 * Date: 30-01-2024
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;
use App\Traits\ApiLog;
use App\Traits\ApiResponse;

class Account extends Model
{
    use HasFactory, SoftDeletes, ApiLog, ApiResponse;
    protected $primaryKey = 'account_id';

    protected $guarded = [];

    /**
     * Account has may projects
     *
     * @return $this
     */
    public function projects()
    {
        return $this->hasMany(Project::class, 'account_id');
    }

    public function getAccount($fields)
    {
        try {
            $this->infoLog('account', __FILE__, __LINE__, "getAccount");
            return $this
                ->select($fields)
                ->join('account_statuses', 'account_statuses.account_status_id', '=', 'accounts.account_status_id')
                ->join('account_types', 'account_types.account_type_id', '=', 'accounts.account_type_id')
                ->leftJoin('states', 'states.state_id', '=', 'accounts.state_id')
                ->join('industries', 'industries.industry_id', '=', 'accounts.industry_id');
        } catch (Exception $exception) {
            $this->debugLog('account', __FILE__, __LINE__, $exception);
            return $this->respondInternalError();
        }
    }

    /**
     * Function to get account detail by account id
     *
     * @param integer $accountId Account Id
     */
    public function getAccountById($accountId = 0)
    {
        try {
            $this->infoLog('account', __FILE__, __LINE__, "Get account by accountId = {$accountId}");
            $fields = [
                'accounts.account_id',
                'accounts.industry_id',
                'accounts.account_type_id',
                'accounts.account_name',
                'accounts.email',
                'accounts.account_desc',
                'accounts.logo',
                'accounts.website',
                'accounts.phone_no',
                'accounts.fax_no',
                'accounts.date_of_estb',
                'accounts.address_1',
                'accounts.address_2',
                'accounts.city',
                'accounts.postal_code',
                'states.state_name',
                'states.country_name',
                'accounts.latitude',
                'accounts.longitude',
                'accounts.primary_contact_id',
                'accounts.created_by',
                'accounts.updated_by',
                'accounts.created_at',
                'accounts.updated_at',
                'account_types.account_type',
                'industries.industry',
                'account_statuses.account_status',
            ];
            $account = $this->getAccount($fields)->findOrFail($accountId);
            if ($account->account_id) {
                return $account;
            }
            return $this->respondNotFound();
        } catch (Exception $exception) {
            $this->debugLog('account', __FILE__, __LINE__, $exception);
            return $this->respondInternalError();
        }
    }

    /**
     * List of accounts
     *
     * @param Integer $limit for pagination
     */
    public function getAccoutList($filters = [])
    {
        try {
            $this->infoLog('account', __FILE__, __LINE__, "Get account List");
            $fields = [
                'accounts.account_id',
                'accounts.account_name',
                'accounts.email',
                'accounts.industry_id',
                'accounts.account_type_id',
                'account_types.account_type',
                'industries.industry',
                'accounts.account_status_id',
                'account_statuses.account_status',

                'accounts.address_1',
                'accounts.address_2',
                'accounts.city',
                'accounts.postal_code',
                'states.state_name',
                'states.country_name',
                'accounts.latitude',
                'accounts.longitude',
            ];
            $limit = intval($filters['limit'] ?? 10);
            $account = $this->getAccount($fields);
            $account = $this->getFilters($account, $filters);
            return $account->paginate($limit);
        } catch (Exception $exception) {
            $this->debugLog('account', __FILE__, __LINE__, $exception);
            return $this->respondInternalError();
        }
    }

    /**
     * List of accounts
     *
     * @param Integer $filters for countW
     */
    public function getCount($filters = [])
    {
        try {
            $this->infoLog('account', __FILE__, __LINE__, "Get account count");

            $statusId = intval($filters['status_id'] ?? 0);
            $typeId = intval($filters['type_id'] ?? 0);

            $account = $this
                ->join('account_statuses', 'account_statuses.account_status_id', '=', 'accounts.account_status_id')
                ->join('account_types', 'account_types.account_type_id', '=', 'accounts.account_type_id')
                ->join('industries', 'industries.industry_id', '=', 'accounts.industry_id');

            if (!empty($statusId)) {
                $account = $account->where('accounts.account_status_id', $statusId);
            }

            if (!empty($typeId)) {
                $account = $account->where('accounts.account_type_id', $typeId);
            }

            return $account->count();
        } catch (Exception $exception) {
            $this->debugLog('account', __FILE__, __LINE__, $exception);
            return $this->respondInternalError();
        }
    }

    private function getFilters($account, $filters)
    {
        $accountName = $filters['account_name'] ?? '';
        $statusId = intval($filters['status_id'] ?? 0);
        $typeId = intval($filters['type_id'] ?? 0);
        $industryId = intval($filters['industry_id'] ?? 0);

        if (!empty($accountName)) {
            $account = $account->where('accounts.account_name', 'like', "%{$accountName}%");
        }

        if (!empty($statusId)) {
            $account = $account->where('accounts.account_status_id', $statusId);
        }

        if (!empty($typeId)) {
            $account = $account->where('accounts.account_type_id', $typeId);
        }

        if (!empty($industryId)) {
            $account = $account->where('accounts.industry_id', $industryId);
        }
        return $account;
    }
}
