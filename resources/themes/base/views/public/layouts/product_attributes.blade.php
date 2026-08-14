@if(!empty($attributes))
    <table class="table table-sm">
        <tbody>
        @foreach($attributes as $attribute => $values)
            <tr>
                <th class="text-muted fw-normal">{{ $attribute }}</th>
                <td>{{ implode(', ', $values) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endif