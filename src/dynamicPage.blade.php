<?php
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;
?>
@extends('layouts.app')

@section('content')
    <dynamic-template template_type_name="<?= $templateTypeName ?>" v-bind:params="<?= str_replace('"', "'", json_encode($templateParams)) ?>"></dynamic-template>
@endsection
