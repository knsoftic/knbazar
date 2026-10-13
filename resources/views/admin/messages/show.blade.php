@extends('layouts.admin')

@section('title', $message->subject)

@section('content')
    <div class="panel-title">
        <div>
            <nav aria-label="breadcrumb"><ol class="breadcrumb mb-1"><li class="breadcrumb-item"><a href="{{ route('admin.messages.index') }}">Messages</a></li><li class="breadcrumb-item active">#{{ $message->id }}</li></ol></nav>
            <h1>{{ $message->subject }}</h1>
        </div>
        <div class="d-flex gap-2">
            <a href="mailto:{{ $message->email }}?subject={{ rawurlencode('Re: '.$message->subject) }}" class="btn btn-primary"><i class="bi bi-reply"></i> Reply by email</a>
            <form method="POST" action="{{ route('admin.messages.toggle', $message) }}">@csrf<button type="submit" class="btn btn-light">Mark as {{ $message->is_read ? 'unread' : 'read' }}</button></form>
            <form method="POST" action="{{ route('admin.messages.destroy', $message) }}" data-confirm="Delete this message?">@csrf @method('DELETE')<button type="submit" class="btn btn-outline-danger"><i class="bi bi-trash"></i></button></form>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-xl-8">
            <div class="card"><div class="card-body" style="white-space: pre-line">{{ $message->message }}</div></div>
        </div>
        <div class="col-xl-4">
            <div class="card">
                <div class="card-body">
                    <ul class="spec-list">
                        <li><span>Name</span><span>{{ $message->name }}</span></li>
                        <li><span>Email</span><span><a href="mailto:{{ $message->email }}">{{ $message->email }}</a></span></li>
                        <li><span>Phone</span><span>{{ $message->phone ?: '-' }}</span></li>
                        <li><span>Received</span><span>{{ $message->created_at->format('M j, Y H:i') }}</span></li>
                        <li><span>IP</span><span class="mono small">{{ $message->ip_address ?: '-' }}</span></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection
