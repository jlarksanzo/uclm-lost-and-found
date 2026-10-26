<?php
namespace App\Http\Controllers;
use App\Models\ItemReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
class ItemReportController extends Controller {
    public function index(Request $request) {
        $filters = $request->validate(['q' => ['nullable','string','max:100'], 'type' => ['nullable',Rule::in(['lost','found'])]]);
        $reports = ItemReport::where('status','approved')
            ->when($filters['type'] ?? null, fn ($query,$type) => $query->where('type',$type))
            ->when($filters['q'] ?? null, fn ($query,$term) => $query->where(function ($query) use ($term) {
                $query->where('name','like','%'.$term.'%')->orWhere('location','like','%'.$term.'%');
            }))->latest()->paginate(12)->withQueryString();
        return view('items.index', compact('reports'));
    }
    public function mine(Request $request) {
        $reports = ItemReport::where('user_id',$request->user()->id)->latest()->paginate(12);
        return view('items.mine',compact('reports'));
    }
    public function store(Request $request) {
        $data = $request->validate([
            'type' => ['required',Rule::in(['lost','found'])],
            'name' => ['required','string','max:100','regex:/\S/'],
            'description' => ['required','string','max:2000','regex:/\S/'],
            'location' => ['required','string','max:150','regex:/\S/'],
            'occurred_on' => ['required','date_format:Y-m-d','before_or_equal:today'],
            'photo' => ['nullable','image','mimes:jpg,jpeg,png,webp','max:2048'],
        ]);
        unset($data['photo']);
        $path = $request->hasFile('photo') ? $request->file('photo')->store('reports','local') : null;
        try {
            ItemReport::create([...$data,'user_id'=>$request->user()->id,'photo_path'=>$path,'status'=>'pending']);
        } catch (\Throwable $error) {
            if ($path) { Storage::disk('local')->delete($path); }
            throw $error;
        }
        return redirect()->route('items.mine')->with('status','Report submitted for review.');
    }
    public function photo(Request $request, ItemReport $report) {
        abort_unless($report->status === 'approved' || $report->user_id === $request->user()->id || $request->user()->is_admin,403);
        abort_unless($report->photo_path && Storage::disk('local')->exists($report->photo_path),404);
        return response()->file(Storage::disk('local')->path($report->photo_path), ['Cache-Control'=>'private, no-store','X-Content-Type-Options'=>'nosniff']);
    }
    public function admin(Request $request) {
        abort_unless($request->user()->is_admin,403);
        $status = $request->validate(['status'=>['nullable',Rule::in(['pending','approved','rejected','returned'])]])['status'] ?? 'pending';
        $reports = ItemReport::with('user')->where('status',$status)->latest()->paginate(12)->withQueryString();
        return view('items.admin',compact('reports','status'));
    }
    public function review(Request $request, ItemReport $report) {
        abort_unless($request->user()->is_admin,403);
        $status = $request->validate(['status'=>['required',Rule::in(['approved','rejected','returned'])]])['status'];
        $allowed = ['pending'=>['approved','rejected'],'approved'=>['returned']];
        abort_unless(in_array($status,$allowed[$report->status] ?? [],true),422);
        $report->update(['status'=>$status]);
        return back()->with('status','Report updated.');
    }
}
