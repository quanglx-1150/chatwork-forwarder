<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use App\Http\Requests\TemplateCreateRequest;
use App\Http\Requests\TemplateUpdateRequest;
use Auth;
use App\Enums\TemplateStatus;
use Illuminate\Support\Facades\DB;
use App\Models\Template;
use App\Models\Condition;
use App\Repositories\Interfaces\TemplateRepositoryInterface as TemplateRepository;

class TemplateController extends Controller
{
    private $templateRepository;

    public function __construct(TemplateRepository $templateRepository)
    {
        $this->templateRepository = $templateRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $perPage = config('paginate.perPage');
        $templates = $this->templateRepository->getAllByUser($perPage);

        return view('templates.index', compact('templates'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('templates.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(TemplateCreateRequest $request)
    {
        $data = $request->only(['name', 'content_type', 'content', 'params']);
        $data['user_id'] = Auth::id();

        DB::beginTransaction();
        try {
            $template = $this->templateRepository->create($data);
            $conditions = $request->only('fields', 'operators', 'values');
            if ($conditions) {
                for ($i = 0; $i < count($conditions['fields']); $i++) {
                    $field = trim($conditions['fields'][$i]);
                    $operator = trim($conditions['operators'][$i]);
                    $value = trim($conditions['values'][$i]);

                    if (!empty($field) && !empty($operator) && !empty($value)) {
                        $template->conditions()->create([
                            'field' => $field,
                            'operator' => $operator,
                            'value' => $value,
                        ]);
                    }
                }
            }
            DB::commit();
            $request->session()->flash('messageSuccess', [
                'status' => 'Create success',
                'message' => 'This template successfully created',
            ]);

            return $template->id;
        } catch (Exception $exception) {
            DB::rollBack();
            return redirect()->back()->with('messageFail', [
                'status' => 'Create failed',
                'message' => 'Create failed. Something went wrong',
            ]);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Template $template)
    {
        $this->authorize('update', $template);

        return view('templates.edit', compact('template'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(TemplateUpdateRequest $request, Template $template)
    {
        $this->authorize('update', $template);
        $data = $request->only([
            'name',
            'content_type',
            'content',
            'params',
        ]);
        $conditions = $request->only('conditions');
        $ids = (array)$request->ids;

        DB::beginTransaction();
        try {
            $template = $this->templateRepository->update($template->id, $data);
            $template->conditions()->whereNotIn('id', $ids)->delete();
            if ($conditions) {
                foreach ($conditions['conditions'] as $condition) {
                    if ($condition['id']) {
                        Condition::whereId($condition['id'])->update($condition);
                    } else {
                        $template->conditions()->create($condition);
                    }
                }
            }

            DB::commit();
            $request->session()->flash('messageSuccess', [
                'status' => 'Update success',
                'message' => 'This template successfully updated',
            ]);

            return $template->id;
        } catch (Exception $exception) {
            DB::rollBack();
            return redirect()->back()->with('messageFail', [
                'status' => 'Update failed',
                'message' => 'Update failed. Something went wrong',
            ])->withInput();
        }
    }

    public function destroy(Request $request, Template $template)
    {
        $page = $request->page ? ['page' => $request->page] : null;
        $this->authorize('delete', $template);

        try {
            $this->templateRepository->delete($template->id);

            return redirect()->route('templates.index', $page)->with('messageSuccess', [
                'status' => 'Delete success',
                'message' => __('message.notification.delete.success', ['object' => 'template']),
            ]);
        } catch (Exception $exception) {
            return redirect()->back()->with('messageFail', [
                'status' => 'Delete failed',
                'message' => __('message.notification.delete.fail', ['object' => 'template']),
            ]);
        }
    }

    public function changeStatus(Request $request, Template $template)
    {
        $this->authorize('update', $template);

        if ($request->status == TemplateStatus::STATUS_PRIVATE) {
            $status = TemplateStatus::STATUS_PRIVATE;
        } elseif ($request->status == TemplateStatus::STATUS_REVIEWING) {
            $status = TemplateStatus::STATUS_REVIEWING;
        }
        $result = $this->templateRepository->update($template->id, ['status' => $status]);

        if ($result) {
            return 'This template was updated successfully';
        }

        return response()->json([
            'status' => 'Updated failed',
            'message' => 'Updated failed. Something went wrong',
        ], 400);
    }
}
