<?php

namespace Tests\Feature;

use App\Models\Claim;
use App\Models\ItemReport;
use App\Models\User;
use App\Notifications\SystemNotice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClaimsAndProgressTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->is_admin = true;
        $user->save();

        return $user;
    }

    private function report(array $data = []): ItemReport
    {
        return ItemReport::create(array_merge([
            'user_id' => User::factory()->create()->id, 'type' => 'found', 'category' => 'Bags',
            'name' => 'Blue canvas backpack', 'description' => 'A blue canvas backpack.',
            'location' => 'Library', 'occurred_on' => now()->toDateString(), 'status' => 'approved',
        ], $data));
    }

    private function form(ItemReport $report): array
    {
        return $report->only(['type', 'category', 'name', 'description', 'location']) + ['occurred_on' => now()->toDateString()];
    }

    public function test_claim_proof_is_private_and_duplicates_are_rejected(): void
    {
        $admin = $this->admin();
        $report = $this->report();
        $claimant = User::factory()->create();
        $proof = 'Private detail: a purple dinosaur stitched inside the pocket.';
        $this->actingAs($claimant)->post(route('claims.store', $report), ['proof' => $proof, 'status' => 'approved'])->assertSessionHasNoErrors();
        $claim = Claim::firstOrFail();
        $this->assertSame('pending', $claim->status);
        $this->get(route('claims.mine'))->assertSee($proof);
        $this->get(route('items.show', $report))->assertSee($proof);
        $this->post(route('claims.store', $report), ['proof' => $proof])->assertSessionHasErrors('proof');
        $this->assertDatabaseCount('claims', 1);
        $this->actingAs($report->user)->get(route('items.show', $report))->assertDontSee($proof);
        $this->get(route('claims.mine'))->assertDontSee($proof);
        $this->get(route('admin.claims'))->assertForbidden();
        $this->actingAs($admin)->get(route('admin.claims'))->assertSee($proof);
        $this->assertSame(1, $admin->unreadNotifications()->count());
    }

    public function test_unavailable_and_self_reports_cannot_be_claimed(): void
    {
        $report = $this->report();
        $this->actingAs($report->user)->post(route('claims.store', $report), ['proof' => str_repeat('Private detail ', 3)])->assertForbidden();
        $other = User::factory()->create();
        foreach (['pending', 'rejected', 'returned'] as $status) {
            $report->update(['status' => $status]);
            $this->actingAs($other)->post(route('claims.store', $report), ['proof' => str_repeat('Private detail ', 3)])->assertForbidden();
        }
        $report->update(['status' => 'approved', 'type' => 'lost']);
        $this->post(route('claims.store', $report), ['proof' => str_repeat('Private detail ', 3)])->assertForbidden();
        $this->assertDatabaseCount('claims', 0);
    }

    public function test_one_approved_claim_and_recorded_handover(): void
    {
        $admin = $this->admin();
        $report = $this->report();
        $one = User::factory()->create();
        $two = User::factory()->create();
        foreach ([$one, $two] as $user) {
            $this->actingAs($user)->post(route('claims.store', $report), ['proof' => 'Hidden label inside the backpack with a unique symbol.']);
        }
        $claims = Claim::orderBy('id')->get();
        $review = ['status' => 'approved', 'review_note' => 'Checked the hidden label with the claimant.'];
        $this->actingAs($one)->patch(route('admin.claims.review', $claims[0]), $review)->assertForbidden();
        $this->actingAs($admin)->patch(route('admin.claims.review', $claims[0]), $review)->assertSessionHasNoErrors();
        $this->assertSame('approved', $claims[0]->fresh()->status);
        $this->assertSame('approved', $report->fresh()->status);
        $this->patch(route('admin.claims.review', $claims[1]), $review)->assertSessionHasErrors('claim');
        $this->post(route('admin.return', $report), ['note' => 'Handover at the office.'])->assertSessionHasErrors('confirmed');
        $this->post(route('admin.return', $report), ['note' => 'Handover at the office.', 'confirmed' => '1'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('return_records', ['item_report_id' => $report->id, 'recipient_id' => $one->id, 'admin_id' => $admin->id]);
        $this->assertSame('returned', $report->fresh()->status);
        $this->assertSame('rejected', $claims[1]->fresh()->status);
        $this->post(route('admin.return', $report), ['note' => 'Duplicate', 'confirmed' => '1'])->assertStatus(409);
        $this->assertDatabaseCount('return_records', 1);
        $this->get(route('admin.history'))->assertOk()->assertSee('Handover at the office.');
        $this->actingAs($one)->get(route('items.show', $report))->assertSee('Return completed');
        $this->get(route('claims.mine'))->assertSee('Approved');
        $this->get(route('items.index'))->assertDontSee('Blue canvas backpack');
        $this->actingAs($two)->get(route('items.show', $report))->assertForbidden();
        $this->get(route('admin.history'))->assertForbidden();
    }

    public function test_admin_cannot_review_own_claim_or_return_without_approval(): void
    {
        $admin = $this->admin();
        $report = $this->report();
        $this->actingAs($admin)->post(route('claims.store', $report), ['proof' => 'My private ownership proof with a unique mark.']);
        $claim = Claim::firstOrFail();
        $this->patch(route('admin.claims.review', $claim), ['status' => 'approved', 'review_note' => 'Self approval'])->assertForbidden();
        $this->post(route('admin.return', $report), ['note' => 'Handover', 'confirmed' => '1'])->assertSessionHasErrors('return');
        $this->assertDatabaseCount('return_records', 0);
    }

    public function test_edits_require_ownership_and_return_for_review(): void
    {
        $admin = $this->admin();
        $report = $this->report();
        $this->actingAs(User::factory()->create())->get(route('items.edit', $report))->assertForbidden();
        $this->patch(route('items.update', $report), $this->form($report))->assertForbidden();
        $this->actingAs($report->user)->get(route('items.edit', $report))->assertOk()->assertSee('Save for review');
        $this->patch(route('items.update', $report), [...$this->form($report), 'name' => 'Corrected backpack', 'status' => 'approved'])->assertSessionHasNoErrors();
        $this->assertSame('pending', $report->fresh()->status);
        $this->assertSame('Corrected backpack', $report->fresh()->name);
        $this->assertSame(1, $admin->unreadNotifications()->count());
        $this->actingAs($admin)->patch(route('admin.review', $report), ['status' => 'approved'])->assertSessionHasNoErrors();
        $this->assertSame(1, $report->user->unreadNotifications()->count());
    }

    public function test_active_claims_and_returns_lock_report_edits(): void
    {
        $report = $this->report();
        $claim = $report->claims()->create(['user_id' => User::factory()->create()->id, 'proof' => 'Private proof of ownership.', 'status' => 'pending']);
        $this->actingAs($report->user)->get(route('items.edit', $report))->assertStatus(409);
        $this->patch(route('items.update', $report), $this->form($report))->assertSessionHasErrors('report');
        $claim->update(['status' => 'rejected']);
        $this->get(route('items.edit', $report))->assertOk();
        $report->update(['status' => 'returned']);
        $this->get(route('items.edit', $report))->assertStatus(409);
    }

    public function test_categories_search_and_matching_hide_unapproved_reports(): void
    {
        $lost = $this->report(['type' => 'lost']);
        $found = $this->report();
        $this->report(['status' => 'pending', 'name' => 'Private backpack']);
        $this->report(['status' => 'returned', 'name' => 'Returned backpack']);
        $this->report(['name' => 'Calculator', 'category' => 'Electronics', 'description' => 'Scientific calculator', 'location' => 'Canteen']);
        $this->assertSame([$found->id], $lost->matches()->pluck('id')->all());
        $this->actingAs($lost->user)->get(route('items.show', $lost))->assertOk()->assertSee('Possible matches')->assertDontSee('Private backpack');
        $this->get('/items?category=Bags&type=found&q=canvas')->assertSee('Blue canvas backpack')->assertDontSee('Calculator')->assertDontSee('Private backpack');
        $this->get('/items?category=Invalid')->assertSessionHasErrors('category');
        $this->get(route('items.show', $found))->assertOk()->assertDontSee('Possible matches');
    }

    public function test_notifications_are_private_and_mark_read_is_scoped(): void
    {
        $one = User::factory()->create();
        $two = User::factory()->create();
        $one->notify(new SystemNotice('Only first user', '/my-reports'));
        $two->notify(new SystemNotice('Only second user', '/my-reports'));
        $this->actingAs($one)->get(route('notifications'))->assertSee('Only first user')->assertDontSee('Only second user');
        $this->post(route('notifications.read'))->assertRedirect();
        $this->assertSame(0, $one->unreadNotifications()->count());
        $this->assertSame(1, $two->unreadNotifications()->count());
    }

    public function test_claim_rejection_notifies_claimant_and_pending_reports_stay_private(): void
    {
        $report = $this->report();
        $claimant = User::factory()->create();
        $claim = $report->claims()->create(['user_id' => $claimant->id, 'proof' => 'Private proof.', 'status' => 'pending']);
        $this->actingAs($this->admin())->patch(route('admin.claims.review', $claim), ['status' => 'rejected', 'review_note' => 'The details did not match.'])->assertSessionHasNoErrors();
        $this->assertSame(1, $claimant->unreadNotifications()->count());
        $report->update(['status' => 'pending']);
        $this->actingAs($claimant)->get(route('items.show', $report))->assertForbidden();
        $this->get(route('claims.mine'))->assertSee('The details did not match.');
    }
}
