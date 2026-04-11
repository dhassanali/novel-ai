<?php

namespace App\Http\Controllers;

use App\Models\Novel;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConsistencyController extends Controller
{
    use AuthorizesRequests;

    public function check(Request $request, Novel $novel): JsonResponse
    {
        $this->authorize('view', $novel);
        set_time_limit(180);

        $novel->load(['chapters', 'characters.relationships.relatedCharacter', 'locations']);

        $loreContext = $this->buildLoreContext($novel);

        $chapterSummaries = $novel->chapters
            ->sortBy('order')
            ->map(function ($chapter) {
                $snippet = mb_substr(strip_tags($chapter->content ?? ''), 0, 800);
                if (mb_strlen($chapter->content ?? '') > 800) {
                    $snippet .= '...';
                }

                return "Chapter {$chapter->order}: {$chapter->title}\n{$snippet}";
            })
            ->implode("\n\n");

        $prompt = <<<PROMPT
You are a professional story editor reviewing a novel for consistency issues.

Novel: {$novel->title}
Genre: {$novel->genre}
Description: {$novel->description}

{$loreContext}

Chapter content (in order):
{$chapterSummaries}

Identify inconsistencies in:
1. Character behavior (actions or dialogue that contradict their stated personality, goals, or backstory)
2. Continuity errors (facts, names, or details that change without explanation)
3. Timeline issues (events that seem out of order or contradict earlier events)
4. World-building contradictions (rules or details about the world that conflict)

Return ONLY a valid JSON object in this exact format, no preamble or explanation:
{"issues":[{"severity":"high","category":"character","description":"..."},{"severity":"medium","category":"plot","description":"..."}],"summary":"..."}

Severity must be one of: high, medium, low
Category must be one of: character, plot, timeline, world
If no issues are found, return: {"issues":[],"summary":"No consistency issues found."}
PROMPT;

        $raw = \App\Facades\LocalAI::generate($prompt);

        $issues = $this->parseIssues($raw);

        return response()->json($issues);
    }

    private function buildLoreContext(Novel $novel): string
    {
        $characterLines = $novel->characters->map(function ($char) {
            $lines = ["- {$char->name} ({$char->role}): {$char->description}"];

            if (! empty($char->personality_traits)) {
                $lines[] = "  Personality: {$char->personality_traits}";
            }
            if (! empty($char->backstory)) {
                $lines[] = "  Backstory: {$char->backstory}";
            }
            if (! empty($char->goals)) {
                $lines[] = "  Goals: {$char->goals}";
            }

            return implode("\n", $lines);
        })->implode("\n");

        $relationshipLines = $novel->characters->flatMap(function ($char) {
            return $char->relationships->map(function ($rel) use ($char) {
                $desc = $rel->description ? " — {$rel->description}" : '';

                return "- {$char->name} ↔ {$rel->relatedCharacter->name} ({$rel->type}){$desc}";
            });
        })->unique()->implode("\n");

        $locationLines = $novel->locations->map(function ($loc) {
            return "- {$loc->name}: {$loc->description}";
        })->implode("\n");

        $context = '';

        if (! empty($characterLines)) {
            $context .= "Characters:\n{$characterLines}\n\n";
        }
        if (! empty($relationshipLines)) {
            $context .= "Character Relationships:\n{$relationshipLines}\n\n";
        }
        if (! empty($locationLines)) {
            $context .= "Locations:\n{$locationLines}\n\n";
        }

        return $context;
    }

    /**
     * @return array{issues: array<array{severity: string, category: string, description: string}>, summary: string}
     */
    private function parseIssues(string $raw): array
    {
        // Strip markdown code fences if the LLM wrapped its response
        $cleaned = preg_replace('/^```(?:json)?\s*/m', '', $raw);
        $cleaned = preg_replace('/\s*```$/m', '', $cleaned ?? $raw);

        // Extract first JSON object from the response
        preg_match('/\{.*\}/s', $cleaned ?? $raw, $matches);

        if (empty($matches[0])) {
            return ['issues' => [], 'summary' => 'Could not parse consistency check results.'];
        }

        $decoded = json_decode($matches[0], true);

        if (! is_array($decoded) || ! isset($decoded['issues'])) {
            return ['issues' => [], 'summary' => 'Could not parse consistency check results.'];
        }

        return [
            'issues' => $decoded['issues'],
            'summary' => $decoded['summary'] ?? '',
        ];
    }
}
