<?php

namespace App\Http\Controllers;

use App\Models\Merchant;
use App\Models\Reward;
use App\Models\User;
use Illuminate\Http\Request;
use Input;
use Validator;
use Response;
use Hash;
use Auth;

class UserController extends ControllerBase
{
	public function postRegister()
	{
		$input = Input::all();
		$rule = [
			'name' => 'required',
			'email' => 'email|unique:users,email',
			'phone' => 'numeric|unique:users,phone|digits_between:10,11',
		    'password' => 'required',
		    'confirm_password' => 'required|same:password',
		];
		$validator = Validator::make($input, $rule);
		if (!$validator->fails()) {
			if (!isset($input['email']) && !isset($input['phone'])) {
				return Response::json([
					'success'=>false,
				    'message' => 'Push phone or email to register',
				]);
			}
			$user = new User();
			$user->name = $input['name'];
			$user->phone = isset($input['phone']) ? $input['phone'] : '';
			$user->email = isset($input['email']) ? $input['email'] : '';
			$user->password = Hash::make($input['password']);
			$user->user_token = md5(time() . rand(0,time()));
			$user->qr_code = md5(time(). rand(0, time()));
			$user->save();
			return Response::json([
				'success'=>true,
			    'message'=> 'Register successfully',
			    'id' => $user->id,
			    'user_token' => $user->user_token,
			]);
		} else {
			return Response::json([
				'success'=>false,
			    'message'=> $this->resolveFailMessage($validator->messages()),
			]);
		}
    }

	public function getInfo($id)
	{
		$user = User::find($id);
		if ($user == null)
			return Response::json([
				'success'=>false,
			    'message'=>'User not found',
			]);
		return Response::json([
			'success'=>true,
		    'user' => $user->getUserInfo(),
		]);
	}

	public function postLogin()
	{
		$input = Input::all();
		$rule  = [
			'key' => 'required',
			'password' => 'required',
		];
		$validator = Validator::make($input, $rule);
		if (!$validator->fails()) {
			if (strpos($input['key'], '@') !== false) {
				$key = 'email';
			} else {
				$key = 'phone';
			}
			$check = Auth::attempt([
		         $key => $input['key'],
				'password' => $input['password'],
			]);
			if ($check) {
				$user = Auth::getUser();
				return Response::json([
					'success' => true,
					'user' => User::find($user->id)->getUserInfo(),
				]);
			} else {
				return Response::json([
					'success'=>false,
				    'message' => 'Login fail'
				]);
			}
		} else {
			return Response::json([
				'success'=>false,
			    'message'=> $this->resolveFailMessage($validator->messages()),
			]);
		}
	}

	public function getInfoFromCode(Request $request)
	{
		$input = Input::all();
		$rule = ['code'=>'required'];
		$validator = Validator::make($input, $rule);
		if (!$validator->fails()) {
			$user = User::where('qr_code', $input['code'])->first();
			if ($user != null) {
				return Response::json([
					'success'=>true,
				    'user' => $user->getUserInfo(),
				]);
			} else {
				return Response::json([
					'success'=>false,
				    'message'=>'User not found',
				]);
			}
		} else {
			return Response::json([
				'success'=>false,
				'message'=>$this->resolveFailMessage($validator->messages()),
			]);
		}
	}

	public function postAdjustPoint()
	{
		$input = Input::all();
		$rule = [
			'user_id' => 'required',
		    'point' => 'numeric|required',
		];
		$validator = Validator::make($input, $rule);
		if (!$validator->fails()) {
			$user = User::find($input['user_id']);
			if  ($user == null)
				return Response::json([
					'success'=>false,
				    'message'=>'User not found',
				]);
			$user->point = $user->point + $input['point'];
			$user->save();
			return Response::json([
				'success'=>true,
			    'message' => 'Adjust point successfully',
			]);
		} else {
			return Response::json([
				'success'=>false,
			    'message'=> $this->resolveFailMessage($validator->messages()),
			]);
		}
	}

	public function getListMerchant()
	{
		$merchants = Merchant::all();
		$count = count($merchants);
		$data = [];
		foreach ($merchants as $merchant) {
			$data[] = $merchant->getMerchantInfo();
		}

		return Response::json([
			'success'=>true,
		    'total' => $count,
			'merchants' => $data,
		]);
	}

	public function getMerchantDetail($id)
	{
		$merchant = Merchant::find($id);
		if ($merchant == null)
			return Response::json([
				'success'=>false,
			    'message'=>'Merchant not found',
			]);
		return Response::json([
			'success'=>true,
		    'merchant' => $merchant->getMerchantInfo(),
		]);
	}

	public function getListReward($id)
	{
		$merchant = Merchant::find($id);
		if ($merchant == null)
			return Response::json([
				'success'=>false,
				'message'=>'Merchant not found',
			]);
		$rewards = Reward::with('merchant_data')
			->where('merchant_id', $id)
			->orderBy('merchant_id', 'DESC')
			->orderBy('id', 'ASC')->get();
		$data    = [];
		foreach ($rewards as $reward) {
			$data[] = $reward->getRewardInfo();
		}

		return Response::json([
			'success' => true,
			'total' => count($data),
			'rewards' => $data,
		]);
	}
}
