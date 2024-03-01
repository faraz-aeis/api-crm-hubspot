<?php

/**
 * Contact Model | Stores records of contact
 *
 * @category Model
 * @author   Anoop Singh <asingh@aeis.com>
 * Date: 30-01-2024
 */

namespace App\Models;

use Dotenv\Repository\Adapter\GuardedWriter;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Exception;

class Contact extends Model
{
    use HasFactory, SoftDeletes;
    protected $primaryKey = 'contact_id';

    protected $guarded = [];
    /* Contact addresses */
    // public function addresses()
    // {
    //     return $this->hasMany(Address::class, 'reference_objet_id')->where('reference_id', 2);
    // }
    // /* Contact communication channels*/
    // public function contactCommChannels()
    // {
    //     return $this->hasMany(ContactCommChannel::class, 'contact_id');
    // }
    // /* Contact Additional Data */
    // public function addData()
    // {
    //     return $this->hasMany(AddData::class, 'contact_id');
    // }

    // public function contactCertifications()
    // {
    //     return $this->hasMany(ContactCertification::class, 'contact_id');
    // }

    // public function contactQualifications()
    // {
    //     return $this->hasMany(ContactQualification::class, 'contact_id');
    // }

    // public function teams()
    // {
    //     return $this->hasMany(Team::class, 'contact_id');
    // }
}
