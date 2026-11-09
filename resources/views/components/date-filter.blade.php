@props(['from', 'to'])
<select name="period" class="form-select" aria-label="Period" onchange="if(this.value){this.form.from.value='';this.form.to.value='';this.form.requestSubmit()}">
    <option value="">Custom range</option>
    @foreach(['today' => 'Today', 'yesterday' => 'Yesterday', 'week' => 'This week', 'last7' => 'Last 7 days', 'last30' => 'Last 30 days', 'month' => 'This month', 'year' => 'This year'] as $k => $v)
        <option value="{{ $k }}" @selected(request('period') === $k)>{{ $v }}</option>
    @endforeach
</select>
<input type="date" name="from" value="{{ $from->toDateString() }}" class="form-control" aria-label="From date" max="{{ now()->toDateString() }}">
<input type="date" name="to" value="{{ $to->toDateString() }}" class="form-control" aria-label="To date" max="{{ now()->toDateString() }}">
