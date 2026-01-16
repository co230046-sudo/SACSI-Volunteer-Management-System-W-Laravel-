<div class="container text-center">

    <!-- PROFILE PHOTO -->
    <img src="{{ $admin->profile_picture 
            ? asset('storage/'.$admin->profile_picture) 
            : asset('assets/adminpic.png') }}"
         class="rounded-circle mb-3"
         style="width:120px;height:120px;object-fit:cover;border:3px solid #b3263a;">

    <!-- NAME + ROLE -->
    <h3 class="fw-bold">{{ $admin->full_name }}</h3>
    <p class="text-muted">{{ strtoupper($admin->role) }}</p>

    <hr>

    <!-- DETAILS GRID -->
    <div class="row text-start">

        <div class="col-md-6 mb-3">
            <strong>Username:</strong>
            <div>{{ $admin->username }}</div>
        </div>

        <div class="col-md-6 mb-3">
            <strong>Email:</strong>
            <div>{{ $admin->email }}</div>
        </div>

        <div class="col-md-6 mb-3">
            <strong>Contact #:</strong>
            <div>{{ $admin->contact_number ?? 'Not Provided' }}</div>
        </div>

        <div class="col-md-6 mb-3">
            <strong>Date Created:</strong>
            <div>{{ $admin->created_at->format('M d, Y') }}</div>
        </div>

    </div>

    <hr>

    <!-- ACTIVITY LOGS -->
    <h5 class="fw-bold mb-3">Recent Activity</h5>

    @if($logs->count())
        <ul class="list-group">
            @foreach($logs->take(5) as $log)
                <li class="list-group-item">
                    {{ $log->action ?? $log->title ?? 'Activity' }}
                    <br>
                    <small class="text-muted">{{ $log->created_at->format('M d, Y h:i A') }}</small>
                </li>
            @endforeach
        </ul>
    @else
        <p class="text-muted">No recent activity.</p>
    @endif

</div>
