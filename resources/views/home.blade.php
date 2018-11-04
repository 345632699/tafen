@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row">
            <div class="col-md-8 col-md-offset-2">
                <div class="panel panel-default">
                    <div class="panel-heading">Dashboard</div>
                    <div class="panel-body">
                        @if (session('status'))
                            <div class="alert alert-success">
                                {{ session('status') }}
                            </div>
                        @endif

                        <a href="{{ route('order.index') }}">查看所有订单</a>
                        <br>
                        <a href="/#/client/list">查看所有用户</a>
                        <br>
                        <a href="/#/banner/list">查看广告banner</a>
                        <br>
                        <a href="/#/order/return-list">查看退货列表</a>
                        <br>
                        <a href="/#/withdraw/list">查看提现列表</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
