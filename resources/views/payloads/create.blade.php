@extends('layouts.app')
@section('content')
<?php use App\Enums\UserType; ?>

<ul class="breadcrumb breadcrumb-top">
    @if (Auth::user()->role == UserType::ADMIN)
        <li><a href="{{ route('admin.webhooks.index') }}">Webhooks</a></li>
    @else
        <li><a href="{{ route('webhooks.index') }}">Webhooks</a></li>
    @endif
    <li><a href="{{ route('webhooks.edit', ['webhook' => $webhook]) }}">Payloads</a></li>
    <li>Create</li>
</ul>

<!-- Simple Editor Block -->
<div class="block">
    <!-- Simple Editor Title -->
    <div class="block-title">
        <h2><strong>New payload</strong></h2>
    </div>
    <!-- END Simple Editor Title -->

    <!-- Simple Editor Content -->
    <input type="hidden" name="webhook_id" value="{{ $webhook->id }}">
    <div class="form-horizontal form-bordered">
        <div class="col-xs-12">
            <div style="padding-top: 10px">
            <span class="fill" id="github" data-toggle="tooltip" data-placement="top" title="Sample template for new Pull Request on Github"> Github</span>
            <span class="fill" id="gitlab" data-toggle="tooltip" data-placement="top" title="Sample template for new Pull Request on Gitlab"> Gitlab</span>
            <span class="fill" id="viblo" data-toggle="tooltip" data-placement="top" title="Sample template for new post on Viblo"> Viblo</span>
            <span class="fill" id="google-form" data-toggle="tooltip" data-placement="top" title="Sample template for Google Form"> Google Form</span>
            <select id="selectTemplate" onchange="selectTemplate({{$templates}}, {{Auth::id()}})" class="select-select2 select-template" data-placeholder="Choose template ...">
                <option></option>
                @foreach ($templates as $index => $template)
                    <option value="{{ $index }}">{{$template->name}} - {{$template->user['name']}}</option>
                @endforeach
            </select>
            <button id="submit" type="submit" class="btn btn-sm btn-primary float-right"><i class="fa fa-check"></i> Save</button>
            <a class="btn btn-sm btn-default float-right cancel-btn"><i class="fa fa-times"></i> Cancel</a>
            </div>
        </div>
        @include('modals.cancel_modal')
        <div class="form-group row">
            <div class="col-xs-12">
                <label class="field-compulsory required">Payload params</label>
                <textarea class="form-control" id="payload_params" rows="7" name="params" placeholder="Enter payload params">{{ old('params') }}</textarea>
                <div class="has-error">
                    <span class="help-block params"></span>
                </div>
            </div>
        </div>
        <div class="form-group row">
            <div class="col-xs-12">
                <label class="field-compulsory">Conditions</label>
                <a href="" data-toggle="modal" data-target="#payloadExample"><i class="fa fa-info-circle"></i> Example</a>
            </div>
            <div class="col-xs-12 mult-condition">
            </div>
            <div class="col-xs-2 has-error">
                <span class="help-block error-field"></span>
            </div>
            <div class="col-xs-1">
            </div>
            <div class="col-xs-2 has-error">
                <span class="help-block error-value"></span>
            </div>
            <div class="col-xs-12">
                <button type="button" class="btn btn--link-primary font-weight-normal" onclick="addFields();"><i class="fa fa-plus-circle"></i>
                    <strong>Add condition</strong>
                </button>
            </div>
        </div>
        <div class="form-group row">
            <div class="col-xs-12">
                <label class="field-compulsory required">Content type</label>
                <a href="" data-toggle="modal" data-target="#contentType"><i class="fa fa-question-circle"></i> What is this?</a>
                <div class="radio">
                    <label>
                        <input type="radio" name="content_type" id="content_text" value="text" {{ (old('content_type') == 'text' || old('content_type') == null) ? 'checked' : ''}}>
                        Text content
                    </label>
                    </div>
                    <div class="radio">
                    <label>
                        <input type="radio" name="content_type" id="content_block" value="blocks" {{ old('content_type') == 'blocks' ? 'checked' : ''}}>
                        Block content
                    </label>
                </div>
            </div>
        </div>
        <div class="form-group row">
            <div class="col-xs-12">
                <label class="field-compulsory required" for="webhook_description">Content</label>
                <a href="" data-toggle="modal" data-target="#contentExample"><i class="fa fa-info-circle"></i> Example</a>
                <textarea class="form-control" id="payload_content" rows="7" name="content" placeholder="Enter Content message">{{ old('content') }}</textarea>
                <div class="has-error">
                    <span class="help-block content"></span>
                </div>
            </div>
        </div>
        <!-- END Simple Editor Content -->
    </div>
</div>
@include('payloads.condition-example')
@include('payloads.content-example')
@include('payloads.content-type')
<!-- END Simple Editor Block -->
@endsection
@section('js')
    <script src="{{ asset('/js/payload.js') }}"></script>
    @include('common.flash-message')
@endsection
