@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="text-center"><h3>Logs</h3></div>

        <table class="table table-bordered">
            <thead>
            <th width="30">No</th>
            <th>Description</th>
            <th width="200">Created</th>
            </thead>
            <tbody>
            @if($data->count() >0)
                <?php $i = 0; ?>

                @foreach($data as $key=>$value)
                    <?php $i++; ?>
                    <tr style="background-color: {{$value->status == 1? "#f5f8fa":"#fff"}}">
                        <td>{{ $i }}</td>
                        <td>{{ $value->description }}</td>
                        <td>{{ $value->created_at }}</td>
                    </tr>
                @endforeach
            @endif
        </table>
        {{$data->links()}}
    </div>


@endsection