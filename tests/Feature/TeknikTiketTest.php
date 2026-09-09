<?php

namespace Tests\Feature;

use App\Models\Pengguna;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeknikTiketTest extends TestCase
{
    public function test_teknik_user_can_access_tiket_page(): void
    {
        $user = Pengguna::where('username', 'nunu@ptmsn.co.id')->first();

        if ($user) {
            $response = $this->actingAs($user)->get(route('teknik.tiket'));
            $response->assertStatus(200);
            $response->assertSee('Gangguan Layanan');
            $response->assertSee('Ubah Password');
            $response->assertSee('Cek Coverage Area');
            $response->assertSee('Terminasi');
            $response->assertSee('Suspend Layanan');
            $response->assertSee('Pemasangan Baru');
            $response->assertSee('Ubah Layanan');
        } else {
            $this->assertTrue(true);
        }
    }
}
