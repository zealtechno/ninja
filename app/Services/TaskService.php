<?php namespace App\Services;

use Auth;
use Utils;
use App\Ninja\Repositories\TaskRepository;
use App\Ninja\Datatables\TaskDatatable;

/**
 * Class TaskService
 */
class TaskService extends BaseService
{
    protected $datatableService;
    protected $taskRepo;

    /**
     * TaskService constructor.
     *
     * @param TaskRepository $taskRepo
     * @param DatatableService $datatableService
     */
    public function __construct(TaskRepository $taskRepo, DatatableService $datatableService)
    {
        $this->taskRepo = $taskRepo;
        $this->datatableService = $datatableService;
    }

    /**
     * @return TaskRepository
     */
    protected function getRepo()
    {
        return $this->taskRepo;
    }

    /**
     * @param $clientPublicId
     * @param $search
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDatatable($clientPublicId, $search)
    {
        $datatable = new TaskDatatable( ! $clientPublicId, $clientPublicId);
        $query = $this->taskRepo->find($clientPublicId, $search);

        if(!Utils::hasPermission('view_all')){
            $query->where('tasks.user_id', '=', Auth::user()->id);
        }

        return $this->datatableService->createDatatable($datatable, $query);
    }
}
