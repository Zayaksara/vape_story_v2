<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_admin_dashboard_loads(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin/dashboard')
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('admin/Dashboard')
                ->has('stats.revenue.current')
                ->has('stats.revenue.previous')
                ->has('stats.revenue.change_pct')
                ->has('stats.transactions.current')
                ->has('stats.profit.current')
                ->has('stats.products_sold.current')
                ->has('period')
                ->has('date_range.start')
                ->has('date_range.end')
                ->has('date_range.prev_start')
                ->has('date_range.prev_end')
            );
    }

    public function test_all_period_types_return_200(): void
    {
        $user    = $this->admin();
        $periods = ['daily', 'weekly', 'monthly', 'quarterly', 'yearly'];

        foreach ($periods as $period) {
            $this->actingAs($user)
                ->get("/admin/dashboard?period={$period}")
                ->assertStatus(200)
                ->assertInertia(fn ($page) => $page->where('period', $period));
        }
    }

    public function test_custom_period_uses_provided_dates(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin/dashboard?period=custom&start_date=2026-05-01&end_date=2026-05-10')
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->where('period', 'custom')
                ->where('date_range.start', '2026-05-01')
                ->where('date_range.end', '2026-05-10')
            );
    }

    public function test_stats_default_to_zero_when_no_sales(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin/dashboard')
            ->assertInertia(fn ($page) => $page
                ->where('stats.revenue.current', 0)
                ->where('stats.transactions.current', 0)
                ->where('stats.products_sold.current', 0)
            );
    }
}
