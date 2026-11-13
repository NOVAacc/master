<?php
namespace App\Http\Controllers;
use App\Models\Feedback;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
class FeedbackController extends Controller {
    public function store(Request $request) {
        if (is_string($request->input('message'))) {
            $request->merge(['message' => trim($request->input('message'))]);
        }
        $data = $request->validate([
            'category' => ['required', Rule::in(['idea', 'bug', 'other'])],
            'message' => ['required', 'string', 'min:3', 'max:5000'],
            'page' => ['nullable', 'string', 'max:255', 'regex:~^/[^?#]*$~'],
        ]);
        Feedback::create([...$data, 'user_id' => $request->user()->id]);
        return response()->json(['message' => 'Спасибо! Ваше сообщение отправлено.'], 201);
    }
    public function index(Request $request) {
        $status = in_array($request->query('status'), ['new', 'reviewed'], true) ? $request->query('status') : null;
        return view('admin.feedback', [
            'feedback' => Feedback::with('user')->when($status, fn ($q) => $q->where('status', $status))->latest()->paginate(20)->withQueryString(),
            'status' => $status,
            'newCount' => Feedback::where('status', 'new')->count(),
        ]);
    }
    public function update(Request $request, Feedback $feedback) {
        $data = $request->validate(['status' => ['required', Rule::in(['new', 'reviewed'])]]);
        $feedback->update($data);
        return back()->with('success', 'Статус обращения обновлён.');
    }
}
