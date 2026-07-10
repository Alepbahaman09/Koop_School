<?php

namespace Tests\Feature\Api;

use App\Http\Controllers\Api\MobileRelationalController;
use App\Models\User;
use Illuminate\Http\Request;
use Tests\TestCase;

class MobileRelationalControllerTest extends TestCase
{
    public function test_upsert_creates_customer_profile_for_new_user(): void
    {
        $user = User::factory()->create([
            'email' => 'mobile-user@example.com',
            'username' => null,
            'phone_number' => null,
            'wallet_balance' => 0,
        ]);

        $request = Request::create('/api/users/'.$user->id, 'POST', [
            'data' => [
                'username' => 'Alice Parent',
                'phoneNumber' => '0123456789',
                'balance' => 10,
            ],
            'merge' => false,
        ]);
        $request->setUserResolver(fn () => $user);

        $controller = new MobileRelationalController();
        $response = $controller->upsert($request, 'users/'.$user->id);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertDatabaseHas('customers', [
            'email' => $user->email,
            'parent_name' => 'Alice Parent',
            'student_name' => 'Alice Parent',
            'phone' => '0123456789',
        ]);
    }
}
