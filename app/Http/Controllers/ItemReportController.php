<?php

namespace App\Http\Controllers;

use App\Models\ItemReport;
use App\Models\User;
use App\Notifications\SystemNotice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ItemReportController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', Rule::in(['lost', 'found'])],
            'category' => ['nullable', Rule::in(ItemReport::CATEGORIES)],
        ]);
        $reports = ItemReport::where('status', 'approved')
            ->when($filters['type'] ?? null, fn ($query, $type) => $query->where('type', $type))
            ->when($filters['category'] ?? null, fn ($query, $category) => $query->where('category', $category))
            ->when($filters['q'] ?? null, fn ($query, $term) => $query->where(function ($query) use ($term) {
                $query->where('name', 'like', '%'.$term.'%')->orWhere('location', 'like', '%'.$term.'%')->orWhere('description', 'like', '%'.$term.'%');
            }))->latest()->paginate(12)->withQueryString();

        return view('items.index', compact('reports'));
    }

    public function mine(Request $request)
    {
        $reports = ItemReport::where('user_id', $request->user()->id)->latest()->paginate(12);

        return view('items.mine', compact('reports'));
    }

    private function validatedReport(Request $request): array
    {
        return $request->validate([
            'type' => ['required', Rule::in(['lost', 'found'])],
            'category' => ['required', Rule::in(ItemReport::CATEGORIES)],
            'name' => ['required', 'string', 'max:100', 'regex:/\S/'],
            'description' => ['required', 'string', 'max:2000', 'regex:/\S/'],
            'location' => ['required', 'string', 'max:150', 'regex:/\S/'],
            'occurred_on' => ['required', 'date_format:Y-m-d', 'before_or_equal:'.now('Asia/Manila')->toDateString()],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validatedReport($request);
        unset($data['photo']);
        $path = $request->hasFile('photo') ? $request->file('photo')->store('reports', 'local') : null;
        try {
            DB::transaction(function () use ($data, $path, $request) {
                $report = ItemReport::create([...$data, 'user_id' => $request->user()->id, 'photo_path' => $path, 'status' => 'pending']);
                Notification::send(User::where('is_admin', true)->get(), new SystemNotice('New report: '.$report->name, route('items.show', $report, false)));
            });
        } catch (\Throwable $error) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }
            throw $error;
        }

        return redirect()->route('items.mine')->with('status', 'Report submitted for review.');
    }

    public function show(Request $request, ItemReport $report)
    {
        abort_unless($report->visibleTo($request->user()), 403);
        $myClaim = $report->claims()->where('user_id', $request->user()->id)->first();
        $approvedClaim = $request->user()->is_admin ? $report->claims()->with('user')->where('status', 'approved')->first() : null;
        $matches = $report->user_id === $request->user()->id && $report->status !== 'returned' ? $report->matches() : collect();
        $returnRecord = $report->status === 'returned' ? $report->returnRecord()->with(['recipient', 'admin'])->first() : null;

        return view('items.show', compact('report', 'myClaim', 'approvedClaim', 'matches', 'returnRecord'));
    }

    public function edit(Request $request, ItemReport $report)
    {
        abort_unless($report->user_id === $request->user()->id, 403);
        abort_unless($report->editable(), 409, 'Reports with active claims or a completed return cannot be edited.');

        return view('items.create', compact('report'));
    }

    public function update(Request $request, ItemReport $report)
    {
        abort_unless($report->user_id === $request->user()->id, 403);
        $data = $this->validatedReport($request);
        unset($data['photo']);
        $path = $request->hasFile('photo') ? $request->file('photo')->store('reports', 'local') : null;
        $oldPath = null;
        try {
            DB::transaction(function () use ($report, $data, $path, &$oldPath) {
                $current = ItemReport::lockForUpdate()->findOrFail($report->id);
                if (! $current->editable()) {
                    throw ValidationException::withMessages(['report' => 'This report has active claims or is already returned.']);
                }
                $oldPath = $current->photo_path;
                $current->update([...$data, 'photo_path' => $path ?? $oldPath, 'status' => 'pending']);
                Notification::send(User::where('is_admin', true)->get(), new SystemNotice('Updated report needs review: '.$current->name, route('items.show', $current, false)));
            });
        } catch (\Throwable $error) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }
            throw $error;
        }
        if ($path && $oldPath) {
            Storage::disk('local')->delete($oldPath);
        }

        return redirect()->route('items.mine')->with('status', 'Changes saved. Your report is awaiting review again.');
    }

    public function photo(Request $request, ItemReport $report)
    {
        abort_unless($report->visibleTo($request->user()), 403);
        abort_unless($report->photo_path && Storage::disk('local')->exists($report->photo_path), 404);

        return response()->file(Storage::disk('local')->path($report->photo_path), ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function admin(Request $request)
    {
        abort_unless($request->user()->is_admin, 403);
        $status = $request->validate(['status' => ['nullable', Rule::in(['pending', 'approved', 'rejected', 'returned'])]])['status'] ?? 'pending';
        $reports = ItemReport::with('user')->where('status', $status)->latest()->paginate(12)->withQueryString();

        return view('items.admin', compact('reports', 'status'));
    }

    public function review(Request $request, ItemReport $report)
    {
        abort_unless($request->user()->is_admin, 403);
        $status = $request->validate(['status' => ['required', Rule::in(['approved', 'rejected'])]])['status'];
        DB::transaction(function () use ($report, $status) {
            $current = ItemReport::lockForUpdate()->findOrFail($report->id);
            abort_unless($current->status === 'pending', 409);
            $current->update(['status' => $status]);
            $current->user->notify(new SystemNotice('Your report "'.$current->name.'" was '.$status.'.', route('items.show', $current, false)));
        });

        return back()->with('status', 'Report updated.');
    }
}
