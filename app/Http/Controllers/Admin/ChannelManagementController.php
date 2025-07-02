<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Channel;

class ChannelManagementController extends Controller
{
    public function index(Request $request)
    {
        $query = Channel::query();
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }
        $channels = $query->orderBy('id', 'desc')->paginate(20)->withQueryString();
        return Inertia::render('Admin/Channels/Index', [
            'channels' => $channels,
            'filters' => $request->only(['search'])
        ]);
    }

    public function show($id)
    {
        $channel = Channel::with(['members.user', 'messages'])->findOrFail($id);
        return Inertia::render('Admin/Channels/Show', [
            'channel' => $channel
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Channels/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:tbl_channels,name',
        ]);
        $channel = Channel::create($validated);
        return redirect()->route('admin.channels.index')->with('success', 'チャンネルを作成しました');
    }

    public function edit($id)
    {
        $channel = Channel::findOrFail($id);
        return Inertia::render('Admin/Channels/Edit', [
            'channel' => $channel
        ]);
    }

    public function update(Request $request, $id)
    {
        $channel = Channel::findOrFail($id);
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:tbl_channels,name,' . $channel->id,
        ]);
        $channel->update($validated);
        return redirect()->route('admin.channels.index')->with('success', 'チャンネルを更新しました');
    }

    public function destroy($id)
    {
        $channel = Channel::findOrFail($id);
        $channel->delete();
        return redirect()->route('admin.channels.index')->with('success', 'チャンネルを削除しました');
    }
} 