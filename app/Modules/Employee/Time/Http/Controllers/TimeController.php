<?php
namespace App\Modules\Employee\Time\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Pim\Repositories\Interfaces\EmployeeRepositoryInterface as EmployeeRepository;
use App\Modules\Time\Http\Requests\TimeLogRequest;
use App\Modules\Employee\Time\Http\Requests\EmployeeTimeLogRequest;
use App\Modules\Time\Repositories\Interfaces\ProjectRepositoryInterface as ProjectRepository;
use App\Modules\Time\Repositories\Interfaces\TimeLogRepositoryInterface as TimeLogRepository;
use Datatables;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TimeController extends Controller {
	private $timeLogRepository;

    public function __construct(TimeLogRepository $timeLogRepository)
    {
        $this->timeLogRepository = $timeLogRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @param  \App\Modules\Time\Repositories\Interfaces\ProjectRepositoryInterface  $projectRepository
     * @param  App\Modules\Pim\Repositories\Interfaces\EmployeeRepositoryInterface  $employeeRepository
     * @return \Illuminate\Http\Response
     */
    public function index(ProjectRepository $projectRepository, EmployeeRepository $employeeRepository)
    {
        $projects = $projectRepository->getAll()->pluck('name', 'id');
        $employees = $employeeRepository->getById(Auth::user()->id)->pluck('first_name', 'id');
        return view('employee.time::index', compact('projects', 'employees'));
    }

    /**
     * Returns data for the resource list
     * 
     * @return \Illuminate\Http\Response
     */
    public function getDatatable()
    {
        return Datatables::of($this->timeLogRepository->getCollection([[
                'key' => 'user_id',
                'operator' => '=',
                'value' => Auth::user()->id
            ]], ['id', 'task_name', 'project_id', 'time', 'date']))
            ->editColumn('project_id', function($time) {
                return $time->project->name;
            })
            ->addColumn('actions', function($time){
                return view('includes._datatable_actions', [
                    'deleteUrl' => route('employee.time.destroy', $time->id), 
                    'editUrl' => route('employee.time.edit', $time->id)
                ]);
            })
            ->make();
    }

    /**
     * Show the form for creating a new resource.
     *
     * @param  \App\Modules\Time\Repositories\Interfaces\ProjectRepositoryInterface  $projectRepository
     * @param  App\Modules\Pim\Repositories\Interfaces\EmployeeRepositoryInterface  $employeeRepository
     * @return \Illuminate\Http\Response
     */
    public function create(ProjectRepository $projectRepository, EmployeeRepository $employeeRepository)
    {
        $projects = $projectRepository->getAll()->pluck('name', 'id');
        return view('employee.time::create', compact('projects'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Modules\Employee\Time\Http\Requests\EmployeeTimeLogRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(EmployeeTimeLogRequest $request)
    {
        $companyData = $request->all()+['user_id' => Auth::user()->id];
        $companyData = $this->timeLogRepository->create($companyData);
        $request->session()->flash('success', trans('app.time.time_logs.store_success'));
        return redirect()->route('employee.time.edit', $companyData->id);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  integer  unique identifier for the resource
     * @param  \App\Modules\Time\Repositories\Interfaces\ProjectRepositoryInterface  $projectRepository
     * @param  App\Modules\Pim\Repositories\Interfaces\EmployeeRepositoryInterface  $employeeRepository
     * @return \Illuminate\Http\Response
     */
    public function edit($id, ProjectRepository $projectRepository, EmployeeRepository $employeeRepository)
    {
        $timeLog = $this->timeLogRepository->getById($id);
        checkValidity($timeLog->user_id);
        $projects = $projectRepository->getAll()->pluck('name', 'id');
        $breadcrumb = ['title' => $timeLog->task_name, 'id' => $timeLog->id];
        return view('employee.time::edit', compact('timeLog', 'projects', 'breadcrumb'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  integer  unique identifier for the resource
     * @param  \App\Modules\Employee\Time\Http\Requests\TimeLogRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function update($id, EmployeeTimeLogRequest $request)
    {
        $timeLogData = $this->timeLogRepository->getById($id);
        checkValidity($timeLogData->user_id);
        $timeLogData = $request->all();
        $timeLogData = $this->timeLogRepository->update($id, $timeLogData);
        $request->session()->flash('success', trans('app.time.time_logs.update_success'));
        return redirect()->route('employee.time.edit', $timeLogData->id);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  integer  unique identifier for the resource
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function destroy($id, Request $request)
    {
        $timeLogData = $this->timeLogRepository->getById($id);
        checkValidity($timeLogData->user_id);
        $this->timeLogRepository->delete($id);
        $request->session()->flash('success', trans('app.time.time_logs.delete_success'));
        return redirect()->route('employee.time.index');
    }
}