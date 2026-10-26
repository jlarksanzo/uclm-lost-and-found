<?php
namespace Tests\Feature;
use App\Models\ItemReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
class ItemReportTest extends TestCase {
    use RefreshDatabase;
    private function data(): array { return ['type'=>'lost','name'=>'Blue umbrella','description'=>'Blue folding umbrella','location'=>'Library','occurred_on'=>now()->toDateString()]; }
    public function test_guests_cannot_access_reporting(): void {
        foreach (['/items','/items/create','/my-reports','/admin/reports'] as $url) { $this->get($url)->assertRedirect('/login'); }
        $this->post('/items',$this->data())->assertRedirect('/login');
    }
    public function test_report_is_pending_and_only_owner_can_see_it(): void {
        $owner=User::factory()->create();
        $other=User::factory()->create();
        $this->actingAs($owner)->post('/items',[...$this->data(),'status'=>'approved','user_id'=>$other->id])->assertRedirect('/my-reports');
        $report=ItemReport::firstOrFail();
        $this->assertSame('pending',$report->status);
        $this->assertSame($owner->id,$report->user_id);
        $this->get('/my-reports')->assertSee('Blue umbrella');
        $this->get('/items')->assertDontSee('Blue umbrella');
        $this->actingAs($other)->get('/my-reports')->assertDontSee('Blue umbrella');
    }
    public function test_admin_approval_and_return_control_visibility(): void {
        $owner=User::factory()->create();
        $admin=User::factory()->create(); $admin->is_admin=true; $admin->save();
        $this->actingAs($owner)->post('/items',$this->data());
        $report=ItemReport::firstOrFail();
        $this->get('/admin/reports')->assertForbidden();
        $this->patch('/admin/reports/'.$report->id,['status'=>'approved'])->assertForbidden();
        $this->actingAs($admin)->get('/admin/reports')->assertOk()->assertSee('Blue umbrella');
        $this->patch('/admin/reports/'.$report->id,['status'=>'approved'])->assertRedirect();
        $this->actingAs($owner)->get('/items?type=lost&q=Library')->assertSee('Blue umbrella');
        $this->get('/items?type=found')->assertDontSee('Blue umbrella');
        $this->actingAs($admin)->patch('/admin/reports/'.$report->id,['status'=>'returned'])->assertRedirect();
        $this->get('/items')->assertDontSee('Blue umbrella');
    }
    public function test_image_is_private_until_approval(): void {
        Storage::fake('local');
        $owner=User::factory()->create();
        $other=User::factory()->create();
        $png=base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII=');
        $this->actingAs($owner)->post('/items',[...$this->data(),'photo'=>UploadedFile::fake()->createWithContent('item.png',$png)])->assertSessionHasNoErrors();
        $report=ItemReport::firstOrFail();
        Storage::disk('local')->assertExists($report->photo_path);
        $this->get('/items/'.$report->id.'/photo')->assertOk();
        $this->actingAs($other)->get('/items/'.$report->id.'/photo')->assertForbidden();
        $report->update(['status'=>'approved']);
        $this->get('/items/'.$report->id.'/photo')->assertOk();
    }
    public function test_invalid_reports_and_admin_self_assignment_are_rejected(): void {
        $this->post('/register',['name'=>'Student','email'=>'test@example.test','password'=>'classroom123','password_confirmation'=>'classroom123','is_admin'=>true]);
        $this->assertFalse((bool)User::firstOrFail()->is_admin);
        $this->post('/items',[...$this->data(),'type'=>'invalid','occurred_on'=>now()->addDay()->toDateString(),'photo'=>UploadedFile::fake()->create('bad.svg',1,'image/svg+xml')])->assertSessionHasErrors(['type','occurred_on','photo']);
        $this->assertDatabaseCount('item_reports',0);
        $this->get('/items/create')->assertOk();
        $this->get('/dashboard')->assertOk();
    }
}
