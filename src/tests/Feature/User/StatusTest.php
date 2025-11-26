<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Models\Attendance;
use Carbon\Carbon;

class StatusTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic feature test example.
     */
    public function test_勤務外の場合、勤怠ステータスが正しく表示される()
    {

        //メール認証スキップ
        $this->withoutMiddleware(\Illuminate\Auth\Middleware\EnsureEmailIsVerified::class);

        //ユーザー作成
        $user = User::create([

            'name' => 'test',
            'email' => 'test@example.com',
            'password' => Hash::make('pass1234'),
            'status' => 'off_duty',
        ]);
        $this->actingAs($user, 'web');

        $response = $this->get('/user/check-in');
        $response->assertStatus(200);

        $response->assertSee('勤務外');


    }

    public function test_出勤中の場合、勤怠ステータスが正しく表示される()
    {

        //メール認証スキップ
        $this->withoutMiddleware(\Illuminate\Auth\Middleware\EnsureEmailIsVerified::class);

        //ユーザー作成
        $user = User::create([
            'name' => 'test',
            'email' => 'test@example.com',
            'password' => Hash::make('pass1234'),
        ]);
        $this->actingAs($user, 'web');

        \App\Models\Attendance::create([
            'user_id' => $user->id,
            'work_date' => now()->toDateString(),
            'clock_in' => now(),
            'status' => 'working',
        ]);

        $response = $this->get('/user/check-in');
        $response->assertStatus(200);

        $response->assertSee('出勤中');

    }

    public function test_休憩中の場合、勤怠ステータスが正しく表示される()
    {

        //メール認証スキップ
        $this->withoutMiddleware(\Illuminate\Auth\Middleware\EnsureEmailIsVerified::class);

        //ユーザー作成
        $user = User::create([

            'name' => 'test',
            'email' => 'test@example.com',
            'password' => Hash::make('pass1234'),
        ]);
        $this->actingAs($user, 'web');

        \App\Models\Attendance::create([
            'user_id' => $user->id,
            'work_date' => now()->toDateString(),
            'clock_in' => now(),
            'status' => 'break',
        ]);

        $response = $this->get('/user/check-in');
        $response->assertStatus(200);

        $response->assertSee('休憩中');


    }

    public function test_退勤済の場合、勤怠ステータスが正しく表示される()
    {

        //メール認証スキップ
        $this->withoutMiddleware(\Illuminate\Auth\Middleware\EnsureEmailIsVerified::class);

        //ユーザー作成
        $user = User::create([

            'name' => 'test',
            'email' => 'test@example.com',
            'password' => Hash::make('pass1234'),
        ]);
        $this->actingAs($user, 'web');

        $attendance = \App\Models\Attendance::create([
            'user_id' => $user->id,
            'work_date' => now()->toDateString(),
            'clock_in' => now(),
            'clock_out' => now(),
            'status' => 'finished',
        ]);

        $response = $this->get('/user/check-in');
        $response->assertStatus(200);

        $response->assertSee('退勤済');

    }
}
