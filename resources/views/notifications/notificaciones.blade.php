@extends('adminlte::page')

@section('title', 'Mis Notificaciones')

@section('content_header')
<h1>Mis Notificaciones</h1>
@stop

@section('content')
<div class="card">
    <div class="card-body">
        @if ($notifications->count() > 0)
        <div class="timeline">
            @foreach ($notifications as $notification)
            <div class="time-label">
                <span class="bg-red">{{ $notification->created_at->format('d/m/Y') }}</span>
            </div>
            <div>
                <i class="{{ $notification->data['icon'] }} bg-blue"></i>
                <div class="timeline-item">
                    <span class="time"><i class="far fa-clock"></i> {{ $notification->created_at->diffForHumans() }}</span>
                    <h3 class="timeline-header">
                        <a href="{{ $notification->data['link'] }}">{{ $notification->data['text'] }} {{ $notification->data['user']}}</a>
                    </h3>
                </div>
            </div>
            @endforeach
        </div>

        <div class="card-footer clearfix">
            {{ $notifications->links('pagination::bootstrap-4') }}
        </div>
        @else
        <p>No tienes notificaciones.</p>
        @endif
    </div>
</div>
@stop