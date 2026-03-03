<x-app-layout>
    <button id="refreshBtn" class="btn btn-primary">Refresh Status</button>

<table class="table mt-3">
    @foreach($services as $name => $val)
    <tr>
        <td>{{ strtoupper($name) }}</td>
        <td>
            <span class="badge {{ $val == 'OK' ? 'bg-success' : 'bg-danger' }}">
                {{ $val }}
            </span>
        </td>
    </tr>
    @endforeach
</table>

<script>
document.getElementById('refreshBtn').addEventListener('click', function() {
    const btn = this;
    btn.disabled = true;
    btn.innerText = 'Checking...';
    
    fetch("{{ route('health.sync') }}", {
        method: 'POST',
        headers: { 
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        }
    })
    .then(response => {
        if (!response.ok) throw response;
        return response.json();
    })
    .then(data => {
        alert('Update Successful!'); // Add this to confirm it worked
        location.reload(); 
    })
    .catch(error => {
        console.error(error);
        alert('Error: Check Laravel Logs or Browser Console');
        btn.disabled = false;
        btn.innerText = 'Refresh Status';
    });
});
</script>
</x-app-layout>
