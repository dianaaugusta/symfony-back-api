<?php

namespace App\Controller;

use App\Entity\Task;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\TaskRepository;
use Doctrine\Persistence\ManagerRegistry;

class TaskController extends AbstractController
{
    //usei o composer req serializer pra ficar o JSON visivel na resposta
    //pra boas práticas, os endpoints sao nomeados com unicidade e no plural
    #[Route('/tasks', name: 'list_tasks', methods: ['GET'])]
    public function getTasks(TaskRepository $taskRepository): JsonResponse
    {
        return $this->json([
            'data' => $taskRepository->findAll(),
        ]);
    }

    /**
     * @throws \Exception
     */
    #[Route('/tasks', name: 'post_tasks', methods: ['POST'])]
    public function postTask(Request $request, TaskRepository $taskRepository): JsonResponse
    {
        if($request->headers->get('Content-Type') == 'application/json'){
            $data = $request->toArray();
        }else{
            $data = $request->request->all();
        }

        $task = new Task();

        if (isset($data['dueDate'])) {
            $dueDate = new \DateTimeImmutable($data['dueDate']);
            $task->setDueDate($dueDate);
        }

        if (isset($data['description'])) {
            $task->setDescription($data['description']);
        }

        $taskRepository->add($task, true);

        return $this->json([
            'message' => 'Task has been created successfully!',
        ], 201);
    }

    // o {} é um parametro de rota, o @PathVariable do spring basicamente
    #[Route('/tasks/{task}', name: 'single_task', methods: ['GET'])]
    public function singleTask(int $task, TaskRepository $taskRepository): JsonResponse
    {
        $task = $taskRepository->find($task);

        if(!$task) throw $this->createNotFoundException();

        return $this->json([
            'data' => $taskRepository->find($task),
        ]);
    }


    //o put e o patch sao para atualização de recurso, mas o PUT atualiza
    // todo o recurso e o patch, apenas uma parte, mas por simplicidade, colocarei os 2 em 1 método
    #[Route('/tasks/{task}', name: 'update_tasks', methods: ['PUT', 'PATCH'])]
    public function updateTask(int $task, Request $request, ManagerRegistry $doctrine, TaskRepository $taskRepository): JsonResponse
    {

        $task = $taskRepository->find($task);
        if(!$task) throw $this->createNotFoundException();

        $data = $request->request->all();
        $task = $taskRepository->find($task);

        if (isset($data['dueDate'])) {
            $dueDate = new \DateTimeImmutable($data['dueDate']);
            $task->setDueDate($dueDate);
        }

        if (isset($data['description'])) {
            $task->setDescription($data['description']);
        }

        $doctrine->getManager()->flush();

        $taskRepository->add($task, true);

        return $this->json([
            'message' => 'Task has been updater successfully!',
            'data'=> $task
        ], 201);
    }

    //"header-type": "application/x-www-form-urlencoded"
    #[Route('/tasks/{task}', name: 'update_tasks', methods: ['DELETE'])]
    public function deleteTask(int $task, Request $request, TaskRepository $taskRepository): JsonResponse
    {
        $task = $taskRepository->find($task);
        $taskRepository->remove($task, true);

        return $this->json([
            'data'=> $task
        ]);
    }
}
