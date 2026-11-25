<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class Agenda extends Model
{
    use HasUuids;

    protected $table = 'agenda';

    protected $primaryKey = 'id_agenda';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id_kelas',
        'tanggal',
        'agenda',
        'mulai',
        'selesai',
        'keterangan',
    ];

    public static function resolveN8nWebhookUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }

        // Convert external Tailscale Funnel URL to internal Docker network URL for n8n container
        $pattern = '#^https?://marahub\.ruffe-augmented\.ts\.net/n8n/(webhook(?:-test)?/.*)$#i';
        if (preg_match($pattern, $url, $matches)) {
            return 'http://n8n:5678/'.$matches[1];
        }

        return $url;
    }

    public static function dispatchN8nReminderWebhook(string $agendaId, string $action): void
    {
        $rawUrl = (string) config('services.n8n.reminder_webhook_url', env('N8N_REMINDER_WEBHOOK_URL', ''));
        $webhookUrl = self::resolveN8nWebhookUrl($rawUrl);

        if ($webhookUrl === '') {
            return;
        }

        DB::afterCommit(function () use ($webhookUrl, $agendaId, $action): void {
            app()->terminating(function () use ($webhookUrl, $agendaId, $action): void {
                try {
                    $response = Http::connectTimeout(3)
                        ->timeout(5)
                        ->acceptJson()
                        ->asJson()
                        ->post($webhookUrl, [
                            'id_agenda' => $agendaId,
                            'action' => $action,
                        ]);

                    if ($response->failed()) {
                        Log::warning('Webhook n8n merespons dengan status gagal.', [
                            'url' => $webhookUrl,
                            'id_agenda' => $agendaId,
                            'action' => $action,
                            'status' => $response->status(),
                            'body' => $response->body(),
                        ]);
                    }
                } catch (Throwable $e) {
                    Log::warning('Gagal mengirim webhook perubahan agenda ke n8n.', [
                        'url' => $webhookUrl,
                        'id_agenda' => $agendaId,
                        'action' => $action,
                        'error' => $e->getMessage(),
                    ]);
                }
            });
        });
    }

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function getIdAttribute(): ?string
    {
        return $this->attributes['id_agenda'] ?? null;
    }

    public function getClassIdAttribute(): ?string
    {
        return $this->attributes['id_kelas'] ?? null;
    }

    public function setClassIdAttribute(?string $value): void
    {
        $this->attributes['id_kelas'] = $value;
    }

    public function getTitleAttribute(): string
    {
        return (string) ($this->attributes['agenda'] ?? '');
    }

    public function setTitleAttribute(?string $value): void
    {
        $this->attributes['agenda'] = $value;
    }

    public function getDescriptionAttribute(): ?string
    {
        return $this->attributes['keterangan'] ?? null;
    }

    public function setDescriptionAttribute(?string $value): void
    {
        $this->attributes['keterangan'] = $value;
    }

    public function getLocationAttribute(): ?string
    {
        return null;
    }

    public function getStartsAtAttribute(): Carbon
    {
        $date = $this->attributes['tanggal'] ?? now()->toDateString();
        $time = ! empty($this->attributes['mulai']) ? substr((string) $this->attributes['mulai'], 0, 8) : '00:00:00';

        return Carbon::parse($date.' '.$time);
    }

    public function getEndsAtAttribute(): ?Carbon
    {
        if (empty($this->attributes['selesai'])) {
            return null;
        }

        $date = $this->attributes['tanggal'] ?? now()->toDateString();
        $time = substr((string) $this->attributes['selesai'], 0, 8);

        return Carbon::parse($date.' '.$time);
    }

    public function getIsPublishedAttribute(): bool
    {
        return true;
    }

    public function getCreatorAttribute(): ?object
    {
        return $this->classRoom?->coordinator;
    }

    /** @return BelongsTo<ClassRoom, $this> */
    public function classRoom(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class, 'id_kelas', 'id_kelas');
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $query;
        }

        $classIds = array_values(array_filter($user->accessibleClassIds()));

        return $query->where(function (Builder $sub) use ($classIds): void {
            $sub->whereNull('id_kelas');
            if (! empty($classIds)) {
                $sub->orWhereIn('id_kelas', $classIds);
            }
        });
    }

    public function isGlobal(): bool
    {
        return false;
    }
}
