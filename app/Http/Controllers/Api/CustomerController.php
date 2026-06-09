<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CustomerController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'student_id' => 'required|unique:customers,student_id',
            'parent_name' => 'required|string',
            'student_name' => 'required|string',
            'email' => 'required|email|unique:customers,email',
            'phone' => 'required|string',
            'class' => 'required|string',
            'address' => 'required|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $customer = Customer::create($request->all());

        return response()->json(['success' => true, 'data' => $customer, 'message' => 'Account registered successfully'], 201);
    }

    public function index()
    {
        $customers = Customer::where('is_active', true)->get();
        return response()->json(['success' => true, 'data' => $customers]);
    }
}
