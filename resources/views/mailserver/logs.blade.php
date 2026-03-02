<x-app-layout>
    <x-slot name="header"><h2>Mail Server Logs</h2></x-slot>
    <div class="p-6 bg-white">
        <table class="table-auto w-full">
            <thead>
                <tr>
                    <th>Server</th><th>Recipient</th><th>Status</th><th>Message</th><th>Sent At</th>
                </tr>
            </thead>
            <tbody>
                @foreach($logs as $log)
                    <tr>
                        <td>{{ $log->mailServer->host ?? 'N/A' }}</td>
                        <td>{{ $log->recipient_email }}</td>
                        <td>{{ ucfirst($log->status) }}</td>
                        <td>{{ $log->message }}</td>
                        <td>{{ $log->created_at }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center">No logs found</td></tr>
                @endforelse
            </tbody>
        </table>
        {{ $logs->links() }}
    </div>
</x-app-layout>
