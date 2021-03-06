<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Reward extends Model
{
    use SoftDeletes;
	protected $table = 'rewards';

	public function merchant_data()
	{
		return $this->belongsTo('App\Models\Merchant', 'merchant_id');
	}

	public function getRewardInfo()
	{
		$result = new \stdClass();
		$result->id = $this->id;
		$result->name = $this->name;
		$result->logo = env('APP_URL', 'https://api.9box.co') . $this->logo;
		$result->description = $this->description;
		$result->points = $this->points;
		$result->quantity = $this->quantity;
		$result->merchant_id = $this->merchant_id;
		return $result;
	}

	public function getUserRewardInfo($userId)
	{
		$result = new \stdClass();
		$result->id = $this->id;
		$result->name = $this->name;
		$result->logo = env('APP_URL', 'https://api.9box.co') . $this->logo;
		$result->description = $this->description;
		$result->points = $this->points;
		$result->quantity = $this->quantity;
		$result->merchant_id = $this->merchant_id;
		$check = UserReward::where('user_id', $userId)
			->where('reward_id', $this->id)->first();
		if ($check != null)
			$result->redeem = true;
		else
			$result->redeem = false;
		return $result;
	}


}
