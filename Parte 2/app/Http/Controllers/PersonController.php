<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePersonRequest;
use App\Http\Requests\UpdatePersonRequest;
use App\Models\Person;
use App\Models\VideoHit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Illuminate\Support\Facades\Cache;

class PersonController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $perPage = request()->get('per_page', 12); // 12 pessoas por página por padrão
        $perPage = min(max($perPage, 1), 100); // Entre 1 e 100
        
        // Buscar pessoas com contagem de detecções de vídeo
        $people = Person::withCount(['videoHits as video_detections'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Para cada pessoa, calcular o total de detecções incluindo imagens
        $people->each(function ($person) {
            // Contar detecções em imagens
            $imageDetections = DB::table('media as m')
                ->where('m.type', 'photo')
                ->where('m.status', 'processed')
                ->whereNotNull('m.meta')
                ->get()
                ->filter(function ($image) use ($person) {
                    if (empty($image->meta)) return false;
                    
                    $meta = json_decode($image->meta, true);
                    if (!isset($meta['detections'])) return false;

                    foreach ($meta['detections'] as $detection) {
                        if (isset($detection['person_id']) && $detection['person_id'] == $person->id) {
                            return true;
                        }
                    }
                    return false;
                })
                ->count();

            // Total de detecções = vídeos + imagens
            $person->total_detections = $person->video_detections + $imageDetections;
        });

        // Aplicar paginação manual
        $currentPage = request()->get('page', 1);
        $offset = ($currentPage - 1) * $perPage;
        $paginatedPeople = $people->slice($offset, $perPage);
        
        // Criar estrutura de paginação
        $total = $people->count();
        $lastPage = ceil($total / $perPage);
        
        $paginatedData = new \Illuminate\Pagination\LengthAwarePaginator(
            $paginatedPeople,
            $total,
            $perPage,
            $currentPage,
            [
                'path' => request()->url(),
                'pageName' => 'page',
            ]
        );

        return Inertia::render('People/Index', [
            'people' => $paginatedData,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('People/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePersonRequest $request)
    {
        $person = Person::create([
            'name' => $request->name,
            'thumbnail_path' => $request->thumbnail_path,
        ]);

        return redirect()->route('people.index')
            ->with('success', 'Pessoa criada com sucesso!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Person $person)
    {
        $perPage = request()->get('per_page', 10);
        $perPage = min(max($perPage, 1), 100);
        $currentPage = request()->get('page', 1);
        
        // Chave de cache única para esta consulta
        $cacheKey = "person_{$person->id}_media_page_{$currentPage}_per_{$perPage}";
        
        // Tenta buscar do cache primeiro
        $paginatedData = Cache::remember($cacheKey, 1, function () use ($person, $perPage) {
            // Consulta para vídeos (usando video_hits)
            $videoDetections = DB::table('video_hits as vh')
                ->join('media as m', 'vh.media_id', '=', 'm.id')
                ->select([
                    'm.id as media_id',
                    'm.path',
                    'm.type',
                    'm.status',
                    'm.created_at as media_created_at',
                    'm.updated_at as media_updated_at',
                    DB::raw('COUNT(vh.id) as total_frames'),
                    DB::raw('MIN(vh.created_at) as first_seen'),
                    DB::raw('MAX(vh.created_at) as last_seen'),
                    DB::raw("'video' as detection_type")
                ])
                ->where('vh.person_id', $person->id)
                ->groupBy('m.id', 'm.path', 'm.type', 'm.status', 'm.created_at', 'm.updated_at')
                ->get();

            Log::info("Video detections found: " . $videoDetections->count());

            // Consulta para imagens (usando meta das imagens) - versão simplificada para teste
            $imageDetections = DB::table('media as m')
                ->select([
                    'm.id as media_id',
                    'm.path',
                    'm.type',
                    'm.status',
                    'm.meta',
                    'm.created_at as media_created_at',
                    'm.updated_at as media_updated_at',
                    DB::raw('1 as total_frames'),
                    DB::raw('m.created_at as first_seen'),
                    DB::raw('m.updated_at as last_seen'),
                    DB::raw("'image' as detection_type")
                ])
                ->where('m.type', 'photo')
                ->where('m.status', 'processed')
                ->whereNotNull('m.meta')
                ->get();

            // Filtrar no PHP para encontrar imagens com detecções da pessoa
            $imageDetections = $imageDetections->filter(function ($image) use ($person) {
                if (empty($image->meta)) return false;
                
                $meta = json_decode($image->meta, true);
                if (!isset($meta['detections'])) return false;

                foreach ($meta['detections'] as $detection) {
                    if (isset($detection['person_id']) && $detection['person_id'] == $person->id) {
                        return true;
                    }
                }
                return false;
            });

            Log::info("Image detections found: " . $imageDetections->count());
            Log::info("Total detections: " . ($videoDetections->count() + $imageDetections->count()));

            // Combinar ambas as consultas no PHP
            $allDetections = $videoDetections->concat($imageDetections)
                ->sortByDesc('last_seen')
                ->values();

            // Criar paginação manual
            $perPage = request('per_page', 15);
            $currentPage = request('page', 1);
            $offset = ($currentPage - 1) * $perPage;
            
            $paginatedData = $allDetections->slice($offset, $perPage);
            $total = $allDetections->count();
            $lastPage = ceil($total / $perPage);

            // Transforma os dados para o formato esperado pelo frontend
            $transformedData = $paginatedData->map(function ($item) {
                return [
                    'media' => [
                        'id' => $item->media_id,
                        'path' => $item->path,
                        'type' => $item->type,
                        'status' => $item->status,
                        'created_at' => $item->media_created_at,
                        'updated_at' => $item->media_updated_at,
                    ],
                    'total_frames' => $item->total_frames,
                    'first_seen' => $item->first_seen,
                    'last_seen' => $item->last_seen,
                ];
            });

            // Cria estrutura de paginação
            return [
                'current_page' => $currentPage,
                'data' => $transformedData->all(),
                'first_page_url' => route('people.show', ['person' => $person->id, 'page' => 1, 'per_page' => $perPage]),
                'from' => $offset + 1,
                'last_page' => $lastPage,
                'last_page_url' => route('people.show', ['person' => $person->id, 'page' => $lastPage, 'per_page' => $perPage]),
                'next_page_url' => $currentPage < $lastPage ? route('people.show', ['person' => $person->id, 'page' => $currentPage + 1, 'per_page' => $perPage]) : null,
                'path' => route('people.show', ['person' => $person->id]),
                'per_page' => $perPage,
                'prev_page_url' => $currentPage > 1 ? route('people.show', ['person' => $person->id, 'page' => $currentPage - 1, 'per_page' => $perPage]) : null,
                'to' => $offset + $paginatedData->count(),
                'total' => $total,
            ];
        });

        return Inertia::render('People/Show', [
            'person' => $person,
            'mediaDetections' => $paginatedData,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Person $person)
    {
        return Inertia::render('People/Edit', [
            'person' => $person,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePersonRequest $request, Person $person)
    {
        $person->update([
            'name' => $request->name,
            'thumbnail_path' => $request->thumbnail_path,
        ]);

        return redirect()->route('people.index')
            ->with('success', 'Pessoa atualizada com sucesso!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Person $person)
    {
        // Remove todos os hits relacionados
        $person->videoHits()->delete();
        
        // Remove a pessoa
        $person->delete();

        return redirect()->route('people.index')
            ->with('success', 'Pessoa removida com sucesso!');
    }

    /**
     * API endpoint para atualizar nome de pessoa
     */
    public function updateName(Request $request, Person $person)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $person->update(['name' => $request->name]);

        // Limpa o cache relacionado a esta pessoa
        $this->clearPersonCache($person->id);

        return response()->json([
            'success' => true,
            'person' => $person->fresh(),
        ]);
    }

    /**
     * Buscar pessoas por nome
     */
    public function search(Request $request)
    {
        $query = $request->get('q', '');
        
        $people = Person::where('name', 'like', "%{$query}%")
            ->withCount(['videoHits as total_detections'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($people);
    }

    /**
     * Limpa o cache relacionado a uma pessoa específica
     */
    private function clearPersonCache(int $personId): void
    {
        // Remove todas as chaves de cache relacionadas a esta pessoa
        $pattern = "person_{$personId}_media_page_*_per_*";
        
        // Se estiver usando Redis, pode usar SCAN para encontrar e remover chaves
        if (config('cache.default') === 'redis') {
            $redis = Cache::getRedis();
            $keys = $redis->keys($pattern);
            foreach ($keys as $key) {
                Cache::forget($key);
            }
        } else {
            // Para outros drivers, limpa o cache manualmente
            // Isso é menos eficiente, mas funciona
            for ($page = 1; $page <= 100; $page++) {
                for ($perPage = 5; $perPage <= 100; $perPage *= 2) {
                    $cacheKey = "person_{$personId}_media_page_{$page}_per_{$perPage}";
                    Cache::forget($cacheKey);
                }
            }
        }
    }
}
