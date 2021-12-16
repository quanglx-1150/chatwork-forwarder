<?php use App\Enums\TemplateStatus; ?>

<div class="modal fade detail-screen-modal" id="detail-{{ $template->id }}" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group detail-template-modal">
                    <div class="">
                        <label class="field-compulsory fl-left">Name</label>
                        <input type="text" class="form-control" value="{{ $template->name }}" readonly>
                    </div>
                    <div class="">
                        <label class="field-compulsory p-6 fl-left">Param</label>
                        <textarea class="form-control" rows="7" readonly>{{ $template->params }}</textarea>
                    </div>
                    <div class="">
                        <label class="field-compulsory p-6 fl-left">Content</label>
                        <textarea class="form-control" rows="3" readonly>{{ $template->content }}</textarea>
                    </div>
                    <div class="">
                        <label class="field-compulsory p-6 fl-left">Status</label>
                        @switch($template->status)
                            @case(TemplateStatus::STATUS_PRIVATE)
                                <?php $status = 'Private'; ?>
                                @break
                            @case(TemplateStatus::STATUS_REVIEWING)
                                <?php $status = 'Reviewing'; ?>
                                @break
                            @case(TemplateStatus::STATUS_PUBLIC)
                                <?php $status = 'Public'; ?>
                                @break
                            @default
                                <?php $status = 'Unpublic'; ?>
                        @endswitch
                        <input type="text" class="form-control" value={{$status}} readonly>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-primary" data-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>
