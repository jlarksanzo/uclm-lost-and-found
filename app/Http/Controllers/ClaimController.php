<?php

namespace App\Http\Controllers;

use App\Models\Claim;
use App\Models\ItemReport;
use App\Models\ReturnRecord;
use App\Models\User;
use App\Notifications\SystemNotice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ClaimController extends Controller
{
    public function store(Request $request, ItemReport $report)
    {
        $data = $request->validate(['proof' => ['required', 'string', 'min:20', 'max:2000', 'regex:/\S/']]);
        DB::transaction(function () use ($request, $report, $data) {
            $current = ItemReport::lockForUpdate()->findOrFail($report->id);
            abort_unless($current->status === 'approved' && $current->type === 'found' && $current->user_id !== $request->user()->id, 403);
            if ($current->claims()->where('user_id', $request->user()->id)->exists()) {
                throw ValidationException::withMessages(['proof' => 'You already submitted a claim for this item.']);
            }
            if ($current->claims()->where('status', 'approved')->exists()) {
                throw ValidationException::withMessages(['proof' => 'This item already has an approved claim.']);
            }
            $current->claims()->create(['user_id' => $request->user()->id, 'proof' => $data['proof'], 'status' => 'pending']);
            Notification::send(User::where('is_admin', true)->get(), new SystemNotice('New ownership claim: '.$current->name, route('admin.claims', [], false)));
        });

        return back()->with('status', 'Claim submitted. Your proof is visible only to you and admins.');
    }

    public function mine(Request $request)
    {
        $claims = Claim::with('report')->where('user_id', $request->user()->id)->latest()->paginate(12);

        return view('claims.index', ['claims' => $claims, 'adminView' => false]);
    }

    public function admin(Request $request)
    {
        abort_unless($request->user()->is_admin, 403);
        $status = $request->validate(['status' => ['nullable', Rule::in(['pending', 'approved', 'rejected'])]])['status'] ?? 'pending';
        $claims = Claim::with(['report', 'user', 'reviewer'])->where('status', $status)->latest()->paginate(12)->withQueryString();

        return view('claims.index', ['claims' => $claims, 'adminView' => true, 'status' => $status]);
    }

    public function review(Request $request, Claim $claim)
    {
        abort_unless($request->user()->is_admin && $claim->user_id !== $request->user()->id, 403);
        $data = $request->validate([
            'status' => ['required', Rule::in(['approved', 'rejected'])],
            'review_note' => ['required', 'string', 'max:1000', 'regex:/\S/'],
        ]);
        DB::transaction(function () use ($request, $claim, $data) {
            $report = ItemReport::lockForUpdate()->findOrFail($claim->item_report_id);
            $current = Claim::lockForUpdate()->findOrFail($claim->id);
            abort_unless($current->status === 'pending' && $report->status === 'approved', 409);
            if ($data['status'] === 'approved' && $report->claims()->where('status', 'approved')->exists()) {
                throw ValidationException::withMessages(['claim' => 'Another claim is already approved for this item.']);
            }
            $current->update([...$data, 'reviewed_by' => $request->user()->id, 'reviewed_at' => now()]);
            $current->user->notify(new SystemNotice('Your claim for "'.$report->name.'" was '.$data['status'].'.', route('claims.mine', [], false)));
        });

        return back()->with('status', 'Claim reviewed. Approval does not record a physical return.');
    }

    public function returned(Request $request, ItemReport $report)
    {
        abort_unless($request->user()->is_admin, 403);
        $data = $request->validate(['note' => ['required', 'string', 'max:1000', 'regex:/\S/'], 'confirmed' => ['accepted']]);
        DB::transaction(function () use ($request, $report, $data) {
            $current = ItemReport::lockForUpdate()->findOrFail($report->id);
            abort_unless($current->status === 'approved', 409);
            $claim = $current->claims()->where('status', 'approved')->first();
            if ($current->type === 'found' && ! $claim) {
                throw ValidationException::withMessages(['return' => 'Approve an ownership claim before recording a return.']);
            }
            $recipient = $claim?->user_id ?? $current->user_id;
            ReturnRecord::create(['item_report_id' => $current->id, 'claim_id' => $claim?->id, 'recipient_id' => $recipient,
                'admin_id' => $request->user()->id, 'note' => $data['note']]);
            $current->update(['status' => 'returned']);
            foreach ($current->claims()->where('status', 'pending')->get() as $other) {
                $other->update(['status' => 'rejected', 'review_note' => 'Item returned to its verified owner.', 'reviewed_by' => $request->user()->id, 'reviewed_at' => now()]);
                $other->user->notify(new SystemNotice('The item "'.$current->name.'" has been returned. Your claim is closed.', route('claims.mine', [], false)));
            }
            Notification::send(User::whereIn('id', array_unique([$recipient, $current->user_id]))->get(),
                new SystemNotice('Return recorded: '.$current->name, route('items.show', $current, false)));
        });

        return redirect()->route('items.show', $report)->with('status', 'Return recorded.');
    }

    public function history(Request $request)
    {
        abort_unless($request->user()->is_admin, 403);
        $records = ReturnRecord::with(['report', 'recipient', 'admin'])->latest()->paginate(12);

        return view('claims.history', compact('records'));
    }
}
