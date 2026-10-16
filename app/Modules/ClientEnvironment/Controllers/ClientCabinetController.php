<?php

namespace App\Modules\ClientEnvironment\Controllers;

use App\Modules\ClientEnvironment\Models\Project;
use App\Modules\ClientEnvironment\Models\BriefTemplate;
use App\Modules\ClientEnvironment\Models\ContractSignature;
use App\Modules\ClientEnvironment\Models\CommunicationTicket;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class ClientCabinetController extends ClientEnvBaseController
{
    #[OA\Get(
        path: '/client/projects',
        summary: 'Мультипроєктний дашборд клієнта: дерево його контрактів та етапів',
        tags: ['Кабінет Клієнта / Інтерфейс'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'client_id', in: 'query', description: 'ID клієнта (тільки для Admin/PM для аудиту)', required: false, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Успішне отримання проєктів клієнта', content: new OA\JsonContent(type: 'array', items: new OA\Items(type: 'object')))
        ]
    )]
    public function myProjects(Request $request): JsonResponse
    {
        $user = $request->user();
        $targetClientId = $user->id;

        if (($user->hasRole('admin') || $user->hasRole('pm')) && $request->has('client_id')) {
            $targetClientId = (int) $request->query('client_id');
        }

        $projects = Project::with(['children.briefTemplate'])
            ->where('client_id', $targetClientId)
            ->whereNull('parent_id')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($projects, 200);
    }

    #[OA\Get(
        path: '/client/projects/{id}/brief/template',
        summary: 'Запит актуальної JSON-схеми брифу для конкретного виробничого етапу',
        tags: ['Кабінет Клієнта / Інтерфейс'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', description: 'ID дочірнього проєкту (етапу)', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Успішне отримання схеми полів брифу'),
            new OA\Response(response: 403, description: 'Доступ заборонено (Project Boundary)'),
            new OA\Response(response: 422, description: 'До цього етапу не привʼязано шаблон брифу')
        ]
    )]
    public function getBriefTemplate(Request $request, int $id): JsonResponse
    {
        $project = Project::where('id', $id)
            ->where('client_id', $request->user()->id)
            ->firstOrFail();

        if (!$project->brief_template_id) {
            return response()->json(['message' => 'Для цього етапу проєкту не налаштовано шаблон динамічного брифу.'], 422);
        }

        return response()->json($project->briefTemplate, 200);
    }

    #[OA\Post(
        path: '/client/projects/{id}/brief/step',
        summary: 'Порційне збереження відповідей брифу клієнтом з динамічною валідацією',
        tags: ['Кабінет Клієнта / Інтерфейс'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', description: 'ID проєкту/етапу', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['step_key', 'answers'],
                properties: [
                    new OA\Property(property: 'step_key', type: 'string', example: 'step_1_company'),
                    new OA\Property(property: 'answers', type: 'object')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Крок брифу успішно збережено'),
            new OA\Response(response: 422, description: 'Помилка динамічної валідації полів')
        ]
    )]
    public function saveBriefStep(Request $request, int $id): JsonResponse
    {
        $project = Project::where('id', $id)
            ->where('client_id', $request->user()->id)
            ->firstOrFail();

        $validated = $request->validate([
            'step_key' => ['required', 'string'],
            'answers' => ['required', 'array']
        ]);

        $stepKey = $validated['step_key'];
        $clientAnswers = $validated['answers'];
        $template = $project->briefTemplate;

        if (!$template || !isset($template->schema_payload[$stepKey])) {
            return response()->json(['message' => 'Вказаний крок брифу не знайдено у конфігурації шаблону.'], 422);
        }

        $fieldsConfig = $template->schema_payload[$stepKey]['fields'] ?? [];
        foreach ($fieldsConfig as $field) {
            $fieldId = $field['field_id'];
            $value = $clientAnswers[$fieldId] ?? null;

            if (!empty($field['required']) && is_null($value)) {
                return response()->json(['message' => "Поле [{$field['label']}] є обовʼязковим для заповнення."], 422);
            }
        }

        $currentBriefData = $project->brief_data ?? [];
        $currentBriefData[$stepKey] = $clientAnswers;

        $project->brief_data = $currentBriefData;
        $project->save();

        return response()->json([
            'message' => 'Дані кроку брифу успішно провалідовані та збережені',
            'brief_data' => $project->brief_data
        ], 200);
    }
    #[OA\Post(
        path: '/client/projects/{id}/contract/sign',
        summary: 'Цифрове підписання договору клієнтом з фіксацією криптографічного SHA-256 сліду',
        tags: ['Кабінет Клієнта / Інтерфейс'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', description: 'ID проєкту/етапу', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Договір успішно підписано')
        ]
    )]
    public function signContract(Request $request, int $id): JsonResponse
    {
        $project = Project::where('id', $id)
            ->where('client_id', $request->user()->id)
            ->firstOrFail();

        $contractRawString = 'PROJECT_ID:' . $project->id . '|BUDGET:' . $project->budget . '|BRIEF_SNAPSHOT:' . json_encode($project->brief_data);
        $documentHash = hash('sha256', $contractRawString);

        $signature = ContractSignature::create([
            'project_id' => $project->id,
            'user_id' => $request->user()->id,
            'document_hash' => $documentHash,
            'ip_address' => $request->ip() ?? '127.0.0.1',
            'user_agent' => $request->userAgent() ?? 'Unknown Browser',
            'signed_at' => now()
        ]);

        $project->update(['status' => 'ready_for_sign']);

        return response()->json([
            'message' => 'Юридичну згоду та цифровий підпис успішно зафіксовано.',
            'signature' => $signature
        ], 200);
    }

    #[OA\Get(
        path: '/client/projects/{id}/tickets',
        summary: 'Перегляд гілки повідомлень тікетів по проєкту (Клієнт бачит тільки текст PM-а)',
        tags: ['Кабінет Клієнта / Інтерфейс'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', description: 'ID проєкту', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Успішне отримання тікетів')
        ]
    )]
    public function getTickets(Request $request, int $id): JsonResponse
    {
        $project = Project::where('id', $id)
            ->where('client_id', $request->user()->id)
            ->firstOrFail();

        $tickets = CommunicationTicket::where('project_id', $project->id)
            ->whereIn('status', ['in_progress', 'waiting_client', 'resolved'])
            ->orderBy('created_at', 'desc')
            ->get();

        $maskedTickets = $tickets->map(function ($ticket) {
            unset($ticket->message_from_specialist);
            return $ticket;
        });

        return response()->json($maskedTickets, 200);
    }

    #[OA\Post(
        path: '/client/projects/{id}/tickets/store',
        summary: 'Створення нового організаційного або технічного тікета клієнтом',
        tags: ['Кабінет Клієнта / Інтерфейс'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', description: 'ID проєкту', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['title', 'category', 'message_to_client'],
                properties: [
                    new OA\Property(property: 'title', type: 'string', example: 'Проблема з відображенням оплати'),
                    new OA\Property(property: 'category', type: 'string', description: 'tech_issue, documents, edits', example: 'tech_issue'),
                    new OA\Property(property: 'message_to_client', type: 'string', example: 'Опис проблеми від клієнта...')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Тікет успішно відкрито')
        ]
    )]
    public function storeTicket(Request $request, int $id): JsonResponse
    {
        $project = Project::where('id', $id)
            ->where('client_id', $request->user()->id)
            ->firstOrFail();

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'in:tech_issue,documents,edits'],
            'message_to_client' => ['required', 'string', 'max:5000']
        ]);

        $ticket = CommunicationTicket::create([
            'project_id' => $project->id,
            'user_id' => $request->user()->id,
            'title' => $validated['title'],
            'category' => $validated['category'],
            'status' => 'open',
            'message_to_client' => $validated['message_to_client'],
            'message_from_specialist' => null
        ]);

        return response()->json([
            'message' => 'Тікет успішно надіслано на розгляд PM.',
            'ticket' => $ticket
        ], 201);
    }
}
